<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;

class PrimitiveCaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::Primitive;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function cast(RouteParam $param, object $input): mixed
    {
        $value = self::getValue($param, $input);
        
        if (self::optionalCheck($param, $value)) {
            return $value;
        }
        
        self::typeCheck($param, $value);
        
        return $value;
    }
}
