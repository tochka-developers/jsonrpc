<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Route\JsonRpcRoute;
use Tochka\JsonRpc\Support\ServerConfig;

/**
 * @method static JsonRpcRoute[] getRoutes()
 * @method static JsonRpcRoute[] getRoutesForServer(string $serverName)
 * @method static string[] getServers()
 * @method static ServerConfig|null getServerConfig(string $serverName)
 * @see \Tochka\JsonRpc\Route\JsonRpcRouteAggregator
 */
class JsonRpcRouteAggregator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
