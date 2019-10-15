<?php

namespace Tochka\JsonRpc\Handlers;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Description\SmdGenerator;
use Tochka\JsonRpc\JsonRpcServer;

class DescriptionSmdHandler implements BaseHandler
{
    /**
     * Handle an incoming request.
     *
     * @param Request       $request
     * @param JsonRpcServer $server
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function handle(Request $request, JsonRpcServer $server)
    {
        if ($request->has('smd')) {
            $server->setResponse((new SmdGenerator($server))->get()->toArray());

            return false;
        }

        return true;
    }
}
