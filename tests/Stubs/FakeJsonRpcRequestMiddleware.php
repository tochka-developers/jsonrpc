<?php

namespace Tochka\JsonRpc\Tests\Stubs;

use Tochka\JsonRpc\Contracts\JsonRpcRequestMiddlewareInterface;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;

class FakeJsonRpcRequestMiddleware implements JsonRpcRequestMiddlewareInterface
{
    public function handleJsonRpcRequest(JsonRpcServerRequest $request, callable $next): JsonRpcResponse
    {
        return $next($request);
    }
}
