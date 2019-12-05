<?php

namespace Tochka\JsonRpc\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcServer;

class AuthHandler implements BaseHandler
{
    /**
     * Handle an incoming request.
     *
     * @param Request       $request
     * @param JsonRpcServer $server
     *
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle(Request $request, JsonRpcServer $server)
    {
        if (!$server->auth) {
            return true;
        }

        if (!$key = $request->header(Config::get('jsonrpc.accessHeaderName', 'Access-Key'))) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        $service = array_search($key, Config::get('jsonrpc.keys', []), true);

        if ($service === false) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        $server->serviceName = $service;

        return true;
    }
}
