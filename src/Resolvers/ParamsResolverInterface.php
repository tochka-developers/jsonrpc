<?php

namespace Tochka\JsonRpc\Resolvers;

use Tochka\JsonRpc\Support\JsonRpcRequest;

interface ParamsResolverInterface
{
    /**
     * @param JsonRpcRequest $request
     * @return mixed
     */
    public function handle(JsonRpcRequest $request);
}
