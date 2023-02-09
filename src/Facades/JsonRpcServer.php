<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\JsonRpcServerInterface;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;

/**
 * @psalm-api
 *
 * @method static JsonRpcResponseCollection handle(ServerRequestInterface $request, string $serverName = 'default', string $group = null, string $action = null)
 * @method static JsonRpcResponse|null handleRequest(JsonRpcServerRequest $request, string $serverName, string $group = null, string $action = null)
 *
 * @see JsonRpcServerInterface
 * @see \Tochka\JsonRpc\JsonRpcServer
 */
class JsonRpcServer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JsonRpcServerInterface::class;
    }
}
