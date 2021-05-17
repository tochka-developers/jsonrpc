<?php

namespace Tochka\JsonRpc\Contracts;

interface GlobalPropertyCasterInterface
{
    public function canCast(string $expectedType, $value, \ReflectionProperty $property, string $fieldName): bool;
    
    public function cast(string $expectedType, $value, \ReflectionProperty $property, string $fieldName);
}
