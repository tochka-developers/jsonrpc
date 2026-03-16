<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Contracts\ShouldMapped;
use Tochka\JsonRpc\Contracts\ShouldValidate;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;

class ParamsCaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::RequestObject;
    }
    
    /**
     * @throws \ReflectionException
     * @throws JsonRpcInvalidParameterException
     */
    public static function cast(RouteParam $param, object $input): mixed
    {
        $class = $param->className;
        
        if (is_subclass_of($class, ShouldValidate::class)) {
            $class::dataValidate($input);
        }
        
        if (is_subclass_of($class, ShouldMapped::class)) {
            return $class::dataMap($input);
        }
        
        return self::createObjectWithoutConstructor($param, $input);
    }
}
