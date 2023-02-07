<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;

/**
 * @psalm-api
 */
interface JsonRpcRequestMiddlewareInterface extends MiddlewareInterface
{
    /**
     * @param JsonRpcServerRequest $request
     * @param callable(JsonRpcServerRequest): JsonRpcResponse $next
     * @return JsonRpcResponse
     */
    public function handleJsonRpcRequest(JsonRpcServerRequest $request, callable $next): JsonRpcResponse;
}
