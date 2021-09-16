<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Route\JsonRpcRoute;

interface JsonRpcRouterInterface
{
    public function get(string $serverName, string $methodName, string $group = null, string $action = null): ?JsonRpcRoute;
    public function add(JsonRpcRoute $route): void;
}
