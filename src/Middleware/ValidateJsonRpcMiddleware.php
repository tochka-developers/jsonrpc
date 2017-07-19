<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcRequest;

class ValidateJsonRpcMiddleware implements BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  JsonRpcRequest $request
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle($request)
    {
        if (empty($request->call->jsonrpc) || $request->call->jsonrpc !== '2.0' || empty($request->call->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        return true;
    }
}
