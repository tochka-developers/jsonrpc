<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\JsonRpc\Annotations\ValidationRule;
use Tochka\JsonRpc\Contracts\CasterRegistryInterface;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\ValidatorInterface;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Exceptions\InvalidTypeException;
use Tochka\JsonRpc\Exceptions\NotNullableValueException;
use Tochka\JsonRpc\Exceptions\ParameterRequiredException;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParametersException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\MethodNotFoundException;

class DefaultHandleResolver implements HandleResolverInterface
{
    private Container $container;
    private ParamsResolverInterface $paramsResolver;
    private CasterRegistryInterface $casterRegistry;
    private ValidatorInterface $validator;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        ParamsResolverInterface $paramsResolver,
        CasterRegistryInterface $casterRegistry,
        ValidatorInterface $validator,
        Container $container
    ) {
        $this->container = $container;
        $this->paramsResolver = $paramsResolver;
        $this->casterRegistry = $casterRegistry;
        $this->validator = $validator;
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(JsonRpcServerRequest $request): mixed
    {
        $controllerInstance = $this->initializeController($request);
        $route = $request->getRoute();
        if ($route === null || $route->controllerMethod === null || !method_exists($controllerInstance, $route->controllerMethod)) {
            throw new MethodNotFoundException();
        }

        $parameters = $this->mapParameters($request->getJsonRpcRequest()->params, $route->parameters);

        return $controllerInstance->{$route->controllerMethod}(...$parameters);
    }

    /**
     * @param array|object|null $rawInputParameters
     * @param array<string, Parameter> $methodParameters
     * @return array
     */
    private function mapParameters(array|object|null $rawInputParameters, array $methodParameters): array
    {
        if ($rawInputParameters === null) {
            return [];
        }

        $parameters = [];

        /** @var array<int, array<int, InvalidParameterError>> $errors */
        $errors = [];

        // если входные параметры переданы массивом - преобразуем в объект с именованными параметрами
        if (is_array($rawInputParameters)) {
            $namedParameters = (object)[];
            $i = 0;
            foreach ($methodParameters as $parameter) {
                if (isset($rawInputParameters[$i])) {
                    $parameterName = $parameter->name;
                    $namedParameters->$parameterName = $rawInputParameters[$i];
                }
                $i++;
            }

            $rawInputParameters = $namedParameters;
        }

        $rules = [];
        foreach ($methodParameters as $parameterName => $parameter) {
            try {
                if ($parameter->castFullRequest && $parameter->className !== null) {  // если необходимо весь запрос скастовать в объект
                    $parameterObject = $this->paramsResolver->getParameterObject($parameter->className);
                    $parameters[] = $this->castObject($rawInputParameters, $parameter, $parameterObject, $parameterName);
                } elseif ($parameter->castFromDI && $parameter->className !== null) { // если необходимо создать объект из DI
                    /** @psalm-suppress MixedAssignment */
                    $parameters[] = $this->container->make($parameter->className);
                } else { // стандартный каст в параметры
                    $rules[$parameterName] = $this->getValidationRules($parameter);
                    /** @psalm-suppress MixedAssignment */
                    $parameters[] = $this->castParameter((array)$rawInputParameters, $parameter, $parameterName);
                }
            } catch (InvalidParameterException $e) {
                $errors[] = [$e->getParameterError()];
            } catch (InvalidParametersException $e) {
                $errors[] = $e->getParametersError()->getParameterErrors();
            } catch (JsonRpcException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw InternalErrorException::from($e);
            }
        }

        $validatorErrors = $this->validator->validateAndGetErrors((array)$rawInputParameters, $rules);
        if (!empty($validatorErrors)) {
            $errors[] = $validatorErrors;
        }

        if (!empty($errors)) {
            /** @var array<int, InvalidParameterError> $combinerErrors */
            $combinerErrors = array_merge(...$errors);
            throw InvalidParametersException::from($combinerErrors);
        }

        return $parameters;
    }

    /**
     * @throws \ReflectionException
     */
    private function castParameter(array $rawInputParameters, Parameter $parameter, string $fullFieldName): mixed
    {
        if (!array_key_exists($parameter->name, $rawInputParameters)) {
            if ($parameter->required) {
                throw new ParameterRequiredException($fullFieldName);
            }

            return $parameter->defaultValue;
        }

        return $this->castValue($rawInputParameters[$parameter->name], $parameter, $fullFieldName);
    }

    /**
     * @throws \ReflectionException
     */
    private function castValue(mixed $value, Parameter $parameter, string $fullFieldName): mixed
    {
        if ($value === null) {
            if (!$parameter->nullable) {
                throw new NotNullableValueException($fullFieldName);
            }

            return null;
        }

        $varType = gettype($value);
        $type = ParameterTypeEnum::fromVarType($varType);

        if ($parameter->className !== null && $parameter->type->is(ParameterTypeEnum::TYPE_OBJECT())) {
            $parameterObject = $this->paramsResolver->getParameterObject($parameter->className);
            $object = $this->castObject($value, $parameter, $parameterObject, $fullFieldName);
            if (!$parameter->nullable && $object === null) {
                throw new NotNullableValueException($fullFieldName);
            }
            return $object;
        }

        if ($type !== $parameter->type && $parameter->type->isNot(ParameterTypeEnum::TYPE_MIXED())) {
            throw new InvalidTypeException($fullFieldName, $type->getValue(), $parameter->type->getValue());
        }

        if ($parameter->parametersInArray !== null && $parameter->type->is(ParameterTypeEnum::TYPE_ARRAY()) && is_array($value)) {
            $resultArray = [];
            $i = 0;

            /** @var array<int, array<int, InvalidParameterError>> $errors */
            $errors = [];

            /** @psalm-suppress MixedAssignment */
            foreach ($value as $inputArrayItem) {
                try {
                    /** @psalm-suppress MixedAssignment */
                    $resultArray[] = $this->castValue(
                        $inputArrayItem,
                        $parameter->parametersInArray,
                        $fullFieldName . '[' . $i . ']'
                    );
                } catch (InvalidParameterException $e) {
                    $errors[] = [$e->getParameterError()];
                } catch (InvalidParametersException $e) {
                    $errors[] = $e->getParametersError()->getParameterErrors();
                } catch (JsonRpcException $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    throw InternalErrorException::from($e);
                }
                $i++;
            }

            if (!empty($errors)) {
                /** @var array<int, InvalidParameterError> $combinerErrors */
                $combinerErrors = array_merge(...$errors);
                throw InvalidParametersException::from($combinerErrors);
            }

            return $resultArray;
        }

        return $value;
    }

    /**
     * @throws \ReflectionException
     */
    private function castObject(
        mixed $value,
        Parameter $parameter,
        ?ParameterObject $parameterObject,
        string $fullFieldName
    ): ?object {
        if ($parameterObject === null) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }

        if ($parameterObject->customCastByCaster !== null) {
            return $this->casterRegistry->cast(
                $parameterObject->customCastByCaster,
                $parameter,
                $value,
                $fullFieldName
            );
        }

        // Создаем экземпляр класса без участия конструктора, так как все равно не можем правильно просадить параметры
        // в конструктор. А так будет возможность в DTO юзать кастомные конструкторы, при этом JsonRpc сможет все равно
        // в него кастить
        $reflectionClass = new \ReflectionClass($parameterObject->className);
        $instance = $reflectionClass->newInstanceWithoutConstructor();

        if ($parameterObject->properties === null) {
            return $instance;
        }

        $propertyValues = (array)$value;

        /** @var array<int, array<int, InvalidParameterError>> $errors */
        $errors = [];

        foreach ($parameterObject->properties as $property) {
            try {
                $propertyName = $property->name;
                if (!$property->required && !array_key_exists($propertyName, $propertyValues)) {
                    continue;
                }

                $instance->$propertyName = $this->castParameter(
                    $propertyValues,
                    $property,
                    $fullFieldName . '.' . $propertyName
                );
            } catch (InvalidParameterException $e) {
                $errors[] = [$e->getParameterError()];
            } catch (InvalidParametersException $e) {
                $errors[] = $e->getParametersError()->getParameterErrors();
            } catch (JsonRpcException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw InternalErrorException::from($e);
            }
        }

        if (!empty($errors)) {
            /** @var array<int, InvalidParameterError> $combinerErrors */
            $combinerErrors = array_merge(...$errors);
            throw InvalidParametersException::from($combinerErrors);
        }

        return $instance;
    }

    /**
     * @throws BindingResolutionException
     */
    private function initializeController(JsonRpcServerRequest $request): object
    {
        $route = $request->getRoute();

        // если нет такого контроллера или метода
        /** @psalm-suppress DocblockTypeContradiction */
        if ($route === null || $route->controllerClass === null || !class_exists($route->controllerClass)) {
            throw new MethodNotFoundException();
        }

        $this->container->when([$route->controllerClass])
            ->needs(JsonRpcServerRequest::class)
            ->give(fn () => $request);

        $this->container->when([$route->controllerClass])
            ->needs(JsonRpcRequest::class)
            ->give(fn () => $request->getJsonRpcRequest());

        $this->container->when([$route->controllerClass])
            ->needs(JsonRpcRoute::class)
            ->give(fn () => $request->getRoute());

        /** @var object $controller */
        $controller = $this->container->make($route->controllerClass);

        if (!is_callable([$controller, $route->controllerMethod])) {
            throw new MethodNotFoundException();
        }

        return $controller;
    }

    private function getValidationRules(Parameter $parameter): array
    {
        $rules = [];
        foreach ($parameter->annotations as $annotation) {
            if ($annotation instanceof ValidationRule) {
                $rules[] = explode('|', $annotation->rule);
            }
        }

        return array_merge(...$rules);
    }
}
