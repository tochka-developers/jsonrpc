<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Route\Parameters\Parameter;

interface CustomCasterInterface
{
    public function cast(Parameter $parameter, mixed $value, string $fieldName): ?object;
}
