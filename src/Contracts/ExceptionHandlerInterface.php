<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Standard\DTO\JsonRpcError;

interface ExceptionHandlerInterface
{
    public function handle(\Throwable $exception): JsonRpcError;
}
