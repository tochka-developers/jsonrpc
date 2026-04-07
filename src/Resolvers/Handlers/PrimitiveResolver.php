<?php

namespace Tochka\JsonRpc\Resolvers\Handlers;

use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class PrimitiveResolver extends AbstractResolver
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
        
        if (self::isVoidAndAllowVoid($param, $value)) {
            return $value;
        }
        
        if (self::isNullAndAllowNull($param, $value)) {
            return $value;
        }
        
        self::typePassOrThrow($param, $value);
        
        return $value;
    }
}
