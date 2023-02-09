<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\DTO\JsonRpcServerRequest;

interface HandleResolverInterface
{
    public function handle(JsonRpcServerRequest $request): mixed;
}
