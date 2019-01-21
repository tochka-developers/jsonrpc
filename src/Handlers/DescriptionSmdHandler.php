<?php

namespace Tochka\JsonRpc\Handlers;

use Illuminate\Http\Request;
use Tochka\JsonRpc\JsonRpcServer;
use Tochka\JsonRpc\Description\SmdGenerator;

class DescriptionSmdHandler implements BaseHandler
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     *
     * @param JsonRpcServer $server
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function handle(Request $request, JsonRpcServer $server)
    {
        if (array_key_exists('smd', $request->all())) {
            $server->setResponse((new SmdGenerator($server))->get()->toArray());

            return false;
        }

        return true;
    }
}
