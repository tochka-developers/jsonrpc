<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\DTO\JsonRpcClient;

interface AuthInterface
{
    public function getClient(): JsonRpcClient;

    public function setClient(JsonRpcClient $client): void;
}
