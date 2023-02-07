<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;

/**
 * @psalm-api
 *
 * @see MiddlewareRegistryInterface
 * @see \Tochka\JsonRpc\Support\MiddlewareRegistry
 */
class JsonRpcMiddlewareRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MiddlewareRegistryInterface::class;
    }
}
