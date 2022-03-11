<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static parseMiddlewares(string $group, array $middlewareConfiguration)
 * @method static getMiddlewareForHttpRequest(string $group): array
 * @method static getMiddlewareForJsonRpcRequest(string $group): array
 * @method static prependMiddleware(object $middleware, ?string $group = null)
 * @method static appendMiddleware(object $middleware, ?string $group = null)
 *
 * @see \Tochka\JsonRpcSupport\Middleware\MiddlewareRepository
 */
class JsonRpcMiddlewareRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
