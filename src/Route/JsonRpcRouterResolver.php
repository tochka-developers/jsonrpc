<?php

namespace Tochka\JsonRpc\Route;

use Tochka\JsonRpc\Contracts\JsonRpcRouterInterface;
use Tochka\JsonRpc\Facades\JsonRpcRouteAggregator;

class JsonRpcRouterResolver implements JsonRpcRouterInterface
{
    private array $routes = [];
    
    public function get(
        string $serverName,
        string $methodName,
        string $group = null,
        string $action = null
    ): ?JsonRpcRoute {
        $requestedRoute = new JsonRpcRoute($serverName, $methodName, $group, $action);
        $requestedRouteName = $requestedRoute->getRouteName();
        
        if (array_key_exists($requestedRouteName, $this->routes)) {
            return $this->routes[$requestedRouteName];
        }
        
        $route = $this->resolveMethod($requestedRoute);
        
        if ($route !== null) {
            $this->routes[$requestedRouteName] = $route;
        }
        
        return $route;
    }
    
    public function add(JsonRpcRoute $route): void
    {
        $this->routes[$route->getRouteName()] = $route;
    }
    
    private function resolveMethod(JsonRpcRoute $requestedRoute): ?JsonRpcRoute
    {
        $routes = JsonRpcRouteAggregator::getRoutesForServer($requestedRoute->serverName);
        
        return $routes[$requestedRoute->getRouteName()] ?? null;
    }
}
