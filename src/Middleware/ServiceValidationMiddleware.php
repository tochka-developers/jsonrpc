<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Support\Facades\Request;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class ServiceValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param JsonRpcRequest    $request
     * @param callable          $next
     * @param array|string|null $servers
     *
     * @return mixed
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function handle(JsonRpcRequest $request, callable $next, $servers = [])
    {
        // если не заданы настройки - по умолчанию запрещаем доступ
        if (empty($servers)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        // если разрешено всем
        if ($servers === '*') {
            return $next($request);
        }

        if (!is_array($servers)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        // если разрешено всем
        if (in_array('*', $servers, true)) {
            return $next($request);
        }

        $ip = Request::ip();
        if (!in_array($ip, $servers, true)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        return $next($request);
    }
}
