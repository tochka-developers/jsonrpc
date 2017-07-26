<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Helpers\LogHelper;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcRequest;

class MethodHideLogDataMiddleware implements BaseMiddleware
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
        if (!empty($request->controller->hideDataLog)) {
            LogHelper::init($request->controller->hideDataLog);
        }
    }
}
