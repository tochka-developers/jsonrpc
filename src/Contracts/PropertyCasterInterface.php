<?php

namespace Tochka\JsonRpc\Contracts;

interface PropertyCasterInterface
{
    public function cast(array $expectedTypes, $value, \ReflectionProperty $property, string $fieldName);
}
