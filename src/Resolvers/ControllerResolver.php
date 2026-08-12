<?php

namespace Tochka\JsonRpc\Resolvers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class ControllerResolver
{
    /**
     * @throws JsonRpcException
     * @throws BindingResolutionException
     */
    public function resolve(JsonRpcRequest $request): object
    {
        $route = $request->getRoute();
        
        // если нет такого контроллера или метода
        if ($route === null || !class_exists($route->controllerClass)) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }
        
        $container = Container::getInstance();
        $controller = $container->make($route->controllerClass);
       
        if (!is_callable([$controller, $route->controllerMethod])) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }
        
        return $controller;
    }
}
