<?php

namespace Tochka\JsonRpc\Tests\Units\Casters;

use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;

trait MakeParameterTrait
{
    private function makeParameter(?string $className): Parameter
    {
        $parameter = new Parameter('test', ParameterTypeEnum::TYPE_OBJECT());
        $parameter->className = $className;

        return $parameter;
    }
}
