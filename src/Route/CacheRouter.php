<?php

namespace Tochka\JsonRpc\Route;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;

class CacheRouter implements RouterInterface
{
    private RouterInterface $router;
    private CacheInterface $cache;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(RouterInterface $router, CacheInterface $cache)
    {
        $this->router = $router;
        $this->cache = $cache;
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
        $route = new JsonRpcRoute($serverName, $methodName, $group, $action);
        $routeName = $route->getRouteName();

        /** @var array<string, JsonRpcRoute> $routes */
        $routes = $this->cache->get('routes', []);
        return $routes[$routeName] ?? $this->router->get($serverName, $methodName, $group, $action);
    }

    public function add(JsonRpcRoute $route): void
    {
        $this->router->add($route);
    }
}
