<?php

namespace Tochka\JsonRpc\Middleware;

use App;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcRequest;

class AccessControlListMiddleware implements BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param JsonRpcRequest $request
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle($request)
    {
        $method = $request->call->method;
        $service = $request->service;

        $acl = isset($request->options['acl'][$method]) ? $request->options['acl'][$method] : null;

        // если нет такого контроллера или метода, или этого метода нет в списке ACL
        if (null === $acl || (
            !($service === 'test' && App::environment() === 'test') && !in_array('*', $acl, true) && !in_array($service, $acl, true))
        ) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        return true;
    }
}
