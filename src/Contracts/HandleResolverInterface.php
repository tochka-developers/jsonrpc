<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Support\JsonRpcRequest;

interface HandleResolverInterface
{
    /**
     * @param JsonRpcRequest $request
     * @return mixed
     */
    public function handle(JsonRpcRequest $request);
}
