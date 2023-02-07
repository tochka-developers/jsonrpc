<?php

namespace Tochka\JsonRpc\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;

/**
 * @psalm-api
 */
interface JsonRpcServerInterface
{
    public function handle(
        ServerRequestInterface $request,
        string $serverName = 'default',
        string $group = null,
        string $action = null
    ): JsonRpcResponseCollection;

    public function handleRequest(
        JsonRpcServerRequest $request,
        string $serverName,
        string $group = null,
        string $action = null
    ): ?JsonRpcResponse;
}
