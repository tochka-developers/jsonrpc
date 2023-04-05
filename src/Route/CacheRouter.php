<?php

namespace Tochka\JsonRpc\Route;

use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;

class CacheRouter implements RouterInterface
{
    use RouteName;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly RouteCacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(
        string $serverName,
        string $methodName,
        string $group = null,
        string $action = null
    ): ?JsonRpcRoute {
        $routeName = $this->getRouteName($serverName, $methodName, $group, $action);

        /** @var array<string, JsonRpcRoute> $routes */
        $routes = $this->cache->get('routes', []);
        return $routes[$routeName] ?? $this->router->get($serverName, $methodName, $group, $action);
    }

    public function add(JsonRpcRoute $route): void
    {
        $this->router->add($route);
    }
}
