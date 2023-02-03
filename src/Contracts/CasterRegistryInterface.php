<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Route\Parameters\Parameter;

interface CasterRegistryInterface
{
    public function addCaster(GlobalCustomCasterInterface $caster): void;

    /**
     * @param class-string $className
     * @return class-string|null
     */
    public function getCasterForClass(string $className): ?string;

    /**
     * @param class-string $casterName
     * @param Parameter $parameter
     * @param mixed $value
     * @param string $fieldName
     * @return object|null
     */
    public function cast(string $casterName, Parameter $parameter, mixed $value, string $fieldName): ?object;
}
