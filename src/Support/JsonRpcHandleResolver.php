<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\JsonRpc\Casters\AbstractPropertyCaster;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParametersException;
use Tochka\JsonRpc\Router\RouteParam;

class JsonRpcHandleResolver implements HandleResolverInterface
{
    /** @var array<AbstractPropertyCaster> */
    protected array $casters;
    
    /**
     * @throws JsonRpcException
     */
    public function __construct(array $casters, array $customCasters = [])
    {
        $this->casters = [...$customCasters, ...$casters];
        foreach ($casters as $caster) {
            if (!is_subclass_of($caster, AbstractPropertyCaster::class)) {
                throw new JsonRpcException(
                    JsonRpcException::CODE_INTERNAL_ERROR,
                    $caster . 'must be child of AbstractPropertyCaster'
                );
            }
        }
    }
    
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
        
        $parameters = $this->mapParameters($request->getParams(), $route->getParams());
        return $controllerInstance->{$request->getRoute()->controllerMethod}(...$parameters);
    }
    
    /**
     * @param array|object $rawInputParameters
     * @param array<RouteParam> $methodParameters
     * @return array
     * @throws JsonRpcInvalidParametersException
     * @throws JsonRpcException
     */
    private function mapParameters(array|object $rawInputParameters, array $methodParameters): array
    {
        $rawInputParameters = (object)$rawInputParameters;
        $parameters = [];
        $errors = [];
        try {
            foreach ($methodParameters as $parameter) {
                foreach ($this->casters as $caster) {
                    if ($caster::canCast($parameter)) {
                        $value = $caster::cast($parameter, $rawInputParameters);
                        if ($value instanceof VoidValue) {
                            continue 2;
                        }
                        $parameters[$parameter->name] = $value;
                        continue 2;
                    }
                }
            }
        } catch (JsonRpcInvalidParametersException $e) {
            $errors[] = $e->getErrors();
        } catch (JsonRpcException $e) {
            throw $e;
        } catch (\Throwable) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }
        
        if (!empty($errors)) {
            throw new JsonRpcInvalidParametersException(array_merge(...$errors));
        }
        
        return $parameters;
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
        
        // todo rm v6
        if (method_exists($controller, 'setJsonRpcRequest')) {
            $controller->setJsonRpcRequest($request);
        }
        
        if (!is_callable([$controller, $route->controllerMethod])) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }
        
        return $controller;
    }
}
