<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Route\JsonRpcRoute;

/**
 * @method static JsonRpcRoute[] getRoutes()
 * @method static JsonRpcRoute[] getRoutesForServer(string $serverName)
 * @see \Tochka\JsonRpc\Route\JsonRpcRouteAggregator
 */
class JsonRpcRouteAggregator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
