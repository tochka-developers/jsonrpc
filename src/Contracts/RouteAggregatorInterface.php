<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\DTO\JsonRpcRoute;

interface RouteAggregatorInterface
{
    /**
     * @return array<JsonRpcRoute>
     */
    public function getRoutes(): array;

    /**
     * @return array<JsonRpcRoute>
     */
    public function getRoutesForServer(string $serverName): array;
}
