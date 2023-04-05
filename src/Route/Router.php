<?php

namespace Tochka\JsonRpc\Route;

use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;

class Router implements RouterInterface
{
    use RouteName;

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
        $routeName = $this->getRouteName($serverName, $methodName, $group, $action);

        if (array_key_exists($routeName, $this->routes)) {
            return $this->routes[$routeName];
        }

        $route = $this->resolveMethod($serverName, $routeName);

        if ($route !== null) {
            $this->routes[$routeName] = $route;
        }

        return $route;
    }

    public function add(JsonRpcRoute $route): void
    {
        $this->routes[$route->getRouteName()] = $route;
    }

    private function resolveMethod(string $serverName, string $routeName): ?JsonRpcRoute
    {
        $routes = $this->routeAggregator->getRoutesForServer($serverName);

        return $routes[$routeName] ?? null;
    }
}
