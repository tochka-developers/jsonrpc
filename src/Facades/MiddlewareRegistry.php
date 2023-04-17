<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;

/**
 * @method static void setMiddleware(string $serverName, array $middleware, array $onceExecutedMiddleware)
 * @method static void append($middleware, ?string $serverName = null)
 * @method static void prepend($middleware, ?string $serverName = null)
 * @method static array getMiddleware(string $serverName)
 * @method static array getOnceExecutedMiddleware(?string $serverName)
 */
class MiddlewareRegistry extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return MiddlewareRegistryInterface::class;
    }
}
