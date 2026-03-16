<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class PrimitiveCaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::Primitive;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function cast(RouteParam $param, JsonRpcRequest $request): mixed
    {
        $value = self::getValue($param, $request->params);
        
        if (self::optionalCheck($param, $value)) {
            return $value;
        }
        
        self::typeCheck($param, $value);
        
        return $value;
    }
}
