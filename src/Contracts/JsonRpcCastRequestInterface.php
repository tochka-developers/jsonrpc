<?php

namespace Tochka\JsonRpc\Contracts;

interface JsonRpcCastRequestInterface extends JsonRpcRequestInterface
{
    public function cast(object $params, string $parentFieldName): void;
}
