<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\ServerConfig;

interface HandleResolverInterface
{
    public function resolve(
        JsonRpcRequest $request,
        ServerConfig $config,
        string $group = null,
        string $action = null
    ): bool;
}
