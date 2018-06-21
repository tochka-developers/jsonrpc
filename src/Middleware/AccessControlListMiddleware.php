<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcRequest;

class AccessControlListMiddleware implements BaseMiddleware
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
        if (empty($request->controller) || empty($request->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR, 'JsonRpc server configuration error: Place AccessControlListMiddleware after MethodClosureMiddleware in middleware list!');
        }

        $controller = \get_class($request->controller);
        $method = $request->method;

        $service = $request->service;

        $acl = $request->options['acl'][$controller][$method] ??
            $request->options['acl'][$controller]['*'] ??
            $request->options['acl'][$controller] ??
            [];

        // если разрешено всем
        if ('*' === $acl) {
            return true;
        }

        // если конфигурация неверная
        if (!\is_array($acl) || empty($acl)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        if (!\in_array('*', $acl, true) && !\in_array($service, $acl, true)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        return true;
    }
}
