<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Route\JsonRpcCacheRouter;
use Tochka\JsonRpc\Route\JsonRpcRoute;
use Tochka\JsonRpc\Route\JsonRpcRouterResolver;

/**
 * @method static JsonRpcRoute|null get(string $serverName, string $methodName, string $group = null, string $action = null)
 * @method static add(JsonRpcRoute $route)
 *
 * @see JsonRpcCacheRouter
 * @see JsonRpcRouterResolver
 */
class JsonRpcRouter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
