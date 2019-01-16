<?php

namespace Tochka\JsonRpc\Handlers;

use Illuminate\Http\Request;
use Tochka\JsonRpc\JsonRpcServer;

interface BaseHandler
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param JsonRpcServer $server
     *
     * @return mixed
     */
    public function handle(Request $request, JsonRpcServer $server);
}