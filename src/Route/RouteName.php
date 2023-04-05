<?php

namespace Tochka\JsonRpc\Route;

trait RouteName
{
    private function getRouteName(string $serverName, string $jsonRpcMethodName, ?string $group, ?string $action): string
    {
        return implode(
            '@',
            [
                $serverName,
                $group,
                $action,
                $jsonRpcMethodName
            ]
        );
    }
}
