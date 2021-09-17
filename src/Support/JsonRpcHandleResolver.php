<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterError;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParametersException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterTypeException;
use Tochka\JsonRpc\Facades\JsonRpcParamsResolver;
use Tochka\JsonRpc\Facades\JsonRpcRequestCast as JsonRpcRequestCastFacade;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;

class JsonRpcHandleResolver implements HandleResolverInterface
{
    /**
     * @throws BindingResolutionException
     * @throws JsonRpcException
     */
    public function handle(JsonRpcRequest $request)
    {
        $controllerInstance = $this->initializeController($request);
        $route = $request->getRoute();
        if ($route === null) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }
        
        $parameters = $this->mapParameters($request->getParams(), $route->parameters);
        
        return $controllerInstance->{$request->getRoute()->controllerMethod}(...$parameters);
    }
    
    /**
     * @param array|object $rawInputParameters
     * @param array<Parameter> $methodParameters
     * @return array
     * @throws JsonRpcInvalidParametersException
     * @throws JsonRpcException
     */
    private function mapParameters($rawInputParameters, array $methodParameters): array
    {
        $parameters = [];
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
        
        foreach ($methodParameters as $parameterName => $parameter) {
            try {
                if ($parameter->castFullRequest) {  // если необходимо весь запрос скастовать в объект
                    $parameterObject = JsonRpcParamsResolver::getParameterObject($parameter->className);
                    $parameters[] = $this->castObject($rawInputParameters, $parameterObject);
                } elseif ($parameter->castFromDI) { // если необходимо создать объект из DI
                    $parameters[] = Container::getInstance()->make($parameter->className);
                } else { // стандартный каст в параметры
                    $parameters[] = $this->castParameter((array)$rawInputParameters, $parameter, $parameterName);
                }
            } catch (JsonRpcInvalidParametersException $e) {
                $errors[] = $e->getErrors();
            } catch (JsonRpcException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
            }
        }
        
        if (!empty($errors)) {
            throw new JsonRpcInvalidParametersException(array_merge(...$errors));
        }
        
        return $parameters;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     * @throws JsonRpcException
     */
    private function castParameter(array $rawInputParameters, Parameter $parameter, string $fullFieldName)
    {
        if (!array_key_exists($parameter->name, $rawInputParameters)) {
            if ($parameter->required) {
                throw new JsonRpcInvalidParameterException(
                    JsonRpcInvalidParameterError::PARAMETER_ERROR_REQUIRED,
                    $fullFieldName
                );
            }
            
            return $parameter->defaultValue;
        }
        
        return $this->castValue($rawInputParameters[$parameter->name], $parameter, $fullFieldName);
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     * @throws JsonRpcException
     */
    private function castValue($value, Parameter $parameter, string $fullFieldName)
    {
        if ($value === null) {
            if (!$parameter->nullable) {
                throw new JsonRpcInvalidParameterException(
                    JsonRpcInvalidParameterError::PARAMETER_ERROR_NOT_NULLABLE,
                    $fullFieldName
                );
            }
            
            return null;
        }
        
        $varType = gettype($value);
        $type = ParameterTypeEnum::fromVarType($varType);
        
        if ($parameter->className !== null && $parameter->type->is(ParameterTypeEnum::TYPE_OBJECT())) {
            $parameterObject = JsonRpcParamsResolver::getParameterObject($parameter->className);
            $object = $this->castObject($value, $parameterObject, $fullFieldName);
            if (!$parameter->nullable && $object === null) {
                throw new JsonRpcInvalidParameterException(
                    JsonRpcInvalidParameterError::PARAMETER_ERROR_NOT_NULLABLE,
                    $fullFieldName
                );
            }
            return $object;
        }
        
        if ($type->isNot($parameter->type) && $parameter->type->isNot(ParameterTypeEnum::TYPE_MIXED())) {
            throw new JsonRpcInvalidParameterTypeException($fullFieldName, $parameter->type->value, $type->value);
        }
        
        if ($parameter->parametersInArray !== null && $parameter->type->is(ParameterTypeEnum::TYPE_ARRAY())) {
            $resultArray = [];
            $i = 0;
            $errors = [];
            foreach ($value as $inputArrayItem) {
                try {
                    $resultArray[] = $this->castValue(
                        $inputArrayItem,
                        $parameter->parametersInArray,
                        $fullFieldName . '[' . $i . ']'
                    );
                } catch (JsonRpcInvalidParametersException $e) {
                    $errors[] = $e->getErrors();
                } catch (JsonRpcException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
                }
                $i++;
            }
            
            if (!empty($errors)) {
                throw new JsonRpcInvalidParametersException(array_merge(...$errors));
            }
            
            return $resultArray;
        }
        
        return $value;
    }
    
    /**
     * @throws JsonRpcException
     * @throws \ReflectionException
     */
    private function castObject($value, ?ParameterObject $parameterObject, string $fullFieldName = null): ?object
    {
        if ($parameterObject === null) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }
        
        if ($parameterObject->customCastByCaster !== null) {
            return JsonRpcRequestCastFacade::cast(
                $parameterObject->customCastByCaster,
                $parameterObject->className,
                $value,
                $fullFieldName
            );
        }
        
        // создаем инстанс класса без участия конструктора, так как все равно не можем правильно просадить параметры
        // в конструктор. А так будет возможность в DTO юзать кастомные конструкторы, при этом JsonRpc сможет все равно
        // в него кастить
        $reflectionClass = new \ReflectionClass($parameterObject->className);
        $instance = $reflectionClass->newInstanceWithoutConstructor();
        
        if ($parameterObject->properties === null) {
            return $instance;
        }
        
        foreach ($parameterObject->properties as $property) {
            try {
                $propertyName = $property->name;
                $instance->$propertyName = $this->castParameter(
                    (array)$value,
                    $property,
                    $fullFieldName . '.' . $propertyName
                );
            } catch (JsonRpcInvalidParametersException $e) {
                $errors[] = $e->getErrors();
            } catch (JsonRpcException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
            }
        }
        
        if (!empty($errors)) {
            throw new JsonRpcInvalidParametersException(array_merge(...$errors));
        }
        
        return $instance;
    }
    
    /**
     * @throws JsonRpcException
     * @throws BindingResolutionException
     */
    private function initializeController(JsonRpcRequest $request): object
    {
        $route = $request->getRoute();
        
        // если нет такого контроллера или метода
        if ($route === null || !class_exists($route->controllerClass)) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }
        
        $container = Container::getInstance();
        $container->when([$route->controllerClass])
            ->needs(JsonRpcRequest::class)
            ->give(fn() => $request);
        
        $controller = $container->make($route->controllerClass);
        
        if (method_exists($controller, 'setJsonRpcRequest')) {
            $controller->setJsonRpcRequest($request);
        }
        
        if (!is_callable([$controller, $route->controllerMethod])) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }
        
        return $controller;
    }
}
