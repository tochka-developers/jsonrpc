<?php

namespace Tochka\JsonRpc\Route;

use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;

class Router implements RouterInterface
{
    /** @var array<string, JsonRpcRoute> */
    private array $routes = [];
    private RouteAggregatorInterface $routeAggregator;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(RouteAggregatorInterface $routeAggregator)
    {
        $this->routeAggregator = $routeAggregator;
    }

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
        $routes = $this->routeAggregator->getRoutesForServer($requestedRoute->serverName);

        return $routes[$requestedRoute->getRouteName()] ?? null;
    }
}
