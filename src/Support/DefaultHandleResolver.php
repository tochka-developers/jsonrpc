<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\Hydrator\Contracts\HydratorInterface;
use Tochka\Hydrator\Definitions\DTO\MethodDefinition;
use Tochka\Hydrator\Exceptions\SameTransformingFieldException;
use Tochka\Hydrator\ExtractFactory;
use Tochka\JsonRpc\Attributes\RequestToDto;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;
use Tochka\JsonRpc\Standard\Exceptions\MethodNotFoundException;
use Tochka\TypeParser\TypeSystem\Types\NamedObjectType;

class DefaultHandleResolver implements HandleResolverInterface
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly Container $container,
        private readonly ExtractFactory $extractor,
        private readonly HydratorInterface $hydrator,
    ) {
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(JsonRpcServerRequest $request): mixed
    {
        $controllerInstance = $this->initializeController($request);
        $route = $request->getRoute();
        if ($route === null || !method_exists($controllerInstance, $route->methodDefinition->methodName)) {
            throw new MethodNotFoundException();
        }

        if ($this->checkMapToDto($route->methodDefinition)) {
            $parameters = $this->mapToDto($request->getJsonRpcRequest()->params, $route->methodDefinition);
        } else {
            try {
                $parameters = $this->extractor->extractToMethodParameters(
                    $request->getJsonRpcRequest()->params,
                    $route->methodDefinition->className,
                    $route->methodDefinition->methodName,
                );
            } catch (SameTransformingFieldException $e) {

            }
        }

        $result = $controllerInstance->{$route->methodDefinition->methodName}(...$parameters);

        return $this->hydrator->hydrate($result, $route->methodDefinition->attributes);
    }

    private function checkMapToDto(MethodDefinition $methodDefinition): bool
    {
        if ($methodDefinition->attributes->has(RequestToDto::class)) {
            return true;
        }

        foreach ($methodDefinition->parameters as $parameter) {
            if ($parameter->attributes->has(RequestToDto::class)) {
                return true;
            }
        }

        return false;
    }

    private function mapToDto(array|null|object $params, MethodDefinition $methodDefinition): array
    {
        $result = [];

        /** @var RequestToDto|null $methodAttribute */
        $methodAttribute = $methodDefinition->attributes->type(RequestToDto::class)->first();
        $mapToDtoParameterName = $methodAttribute?->parameterName;

        foreach ($methodDefinition->parameters as $parameter) {
            if ($parameter->type instanceof NamedObjectType) {
                if ($parameter->name === $mapToDtoParameterName || $parameter->attributes->has(RequestToDto::class)) {
                    $result[] = $this->extractor->extractToObject($params, $parameter->type->className);
                } else {
                    try {
                        $result[] = $this->container->make($parameter->type->className);
                    } catch (BindingResolutionException $e) {
                        throw InternalErrorException::from($e);
                    }
                }
            } else {
                throw new InternalErrorException('');
            }
        }

        return $result;
    }

    /**
     * @throws BindingResolutionException
     */
    private function initializeController(JsonRpcServerRequest $request): object
    {
        $route = $request->getRoute();

        // если нет такого контроллера или метода
        /** @psalm-suppress DocblockTypeContradiction */
        if ($route === null || !class_exists($route->methodDefinition->className)) {
            throw new MethodNotFoundException();
        }

        $this->container->when([$route->methodDefinition->className])
            ->needs(JsonRpcServerRequest::class)
            ->give(fn () => $request);

        $this->container->when([$route->methodDefinition->className])
            ->needs(JsonRpcRequest::class)
            ->give(fn () => $request->getJsonRpcRequest());

        $this->container->when([$route->methodDefinition->className])
            ->needs(JsonRpcRoute::class)
            ->give(fn () => $request->getRoute());

        /** @var object $controller */
        $controller = $this->container->make($route->methodDefinition->className);

        if (!is_callable([$controller, $route->methodDefinition->methodName])) {
            throw new MethodNotFoundException();
        }

        return $controller;
    }
}
