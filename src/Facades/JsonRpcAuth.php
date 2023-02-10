<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\AuthInterface;
use Tochka\JsonRpc\DTO\JsonRpcClient;

/**
 * @psalm-api
 *
 * @method static JsonRpcClient getClient();
 * @method static void setClient(JsonRpcClient $client)
 *
 * @see AuthInterface
 * @see \Tochka\JsonRpc\Support\Auth
 */
class JsonRpcAuth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthInterface::class;
    }
}
