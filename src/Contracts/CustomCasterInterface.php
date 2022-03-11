<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Route\Parameters\Parameter;

interface CustomCasterInterface
{
    public function cast(Parameter $parameter, $value, string $fieldName);
}
