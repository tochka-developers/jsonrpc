<?php

namespace Tochka\JsonRpc\Contracts;

interface CustomCasterInterface
{
    public function cast(string $expectedType, $value, string $fieldName);
}
