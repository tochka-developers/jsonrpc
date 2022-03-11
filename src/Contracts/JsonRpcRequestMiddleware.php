<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\JsonRpcResponse;
use Tochka\JsonRpcSupport\Contracts\JsonRpcRequestMiddleware as BaseMiddleware;

interface JsonRpcRequestMiddleware extends BaseMiddleware
{
    public function handleJsonRpcRequest(JsonRpcRequest $request, callable $next): JsonRpcResponse;
}
