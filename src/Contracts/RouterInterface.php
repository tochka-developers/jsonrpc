<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\DTO\JsonRpcRoute;

interface RouterInterface
{
    public function get(string $serverName, string $methodName, string $group = null, string $action = null): ?JsonRpcRoute;
    public function add(JsonRpcRoute $route): void;
}
