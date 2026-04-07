<?php

namespace Tochka\JsonRpc\Resolvers\Handlers;

use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

class MixedResolver extends AbstractResolver
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::Mixed;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function cast(RouteParam $param, JsonRpcRequest $request): mixed
    {
        $value = self::getValue($param, $request->params);
        
        if (self::isVoidAndAllowVoid($param, $value)) {
            return new VoidValue();
        }
        
        return $value;
    }
}
