<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Contracts\ShouldMapped;
use Tochka\JsonRpc\Contracts\ShouldValidate;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;

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
    public static function cast(RouteParam $param, JsonRpcRequest $request): mixed
    {
        $class = $param->className;
        
        if (is_subclass_of($class, ShouldValidate::class)) {
            $class::dataValidate($request->params);
        }
        
        if (is_subclass_of($class, ShouldMapped::class)) {
            return $class::dataMap($request->params);
        }
        
        return self::createObjectWithoutConstructor($param, $request->params);
    }
}
