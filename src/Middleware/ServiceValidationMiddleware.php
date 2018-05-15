<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Support\Facades\Request;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcRequest;

class ServiceValidationMiddleware implements BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param JsonRpcRequest $request
     *
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle($request)
    {
        $allow_ips = config('jsonrpc.servers.' . $request->service);

        // если не заданы настройки - по умолчанию запрещаем доступ
        if (null === $allow_ips) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        // если разрешено всем
        if ($allow_ips === '*') {
            return true;
        }

        if (!\is_array($allow_ips)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        // если разрешено всем
        if (\in_array('*', $allow_ips, true)) {
            return true;
        }

        $ip = Request::ip();
        if (!\in_array($ip, $allow_ips, true)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        return true;
    }
}
