<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;

/**
 * @method static JsonRpcRoute|null get(string $serverName, string $methodName, string $group = null, string $action = null)
 * @method static void add(JsonRpcRoute $route)
 *
 * @see RouterInterface
 * @see \Tochka\JsonRpc\Route\CacheRouter
 * @see \Tochka\JsonRpc\Route\Router
 */
class JsonRpcRouter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RouterInterface::class;
    }
}
