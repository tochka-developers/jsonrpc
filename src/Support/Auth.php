<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Contracts\AuthInterface;
use Tochka\JsonRpc\DTO\JsonRpcClient;

class Auth implements AuthInterface
{
    private JsonRpcClient $client;

    public function __construct()
    {
        $this->client = new JsonRpcClient();
    }

    public function getClient(): JsonRpcClient
    {
        return $this->client;
    }

    public function setClient(JsonRpcClient $client): void
    {
        $this->client = $client;
    }
}
