<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Contracts\ShouldMapped;
use Tochka\JsonRpc\Contracts\ShouldValidate;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;

class ObjectCaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::Object;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     * @throws \ReflectionException
     */
    public static function cast(RouteParam $param, object $input): mixed
    {
        $value = self::getValue($param, $input);
        if (self::optionalCheck($param, $value)) {
            return $value;
        }
        
        if (self::typeCheck($param, $value)) {
            return $value;
        }
        
        $class = $param->className;
        
        if (is_subclass_of($class, ShouldValidate::class)) {
            $class::dataValidate($input);
        }
        
        if (is_subclass_of($class, ShouldMapped::class)) {
            return $class::dataMap($input);
        }
        return self::createObjectWithoutConstructor($param, $value);
    }
}
