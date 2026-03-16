<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Contracts\ShouldMapped;
use Tochka\JsonRpc\Contracts\ShouldValidate;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;

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
    public static function cast(RouteParam $param, JsonRpcRequest $request): mixed
    {
        $value = self::getValue($param, $request->params);
        if (self::optionalCheck($param, $value)) {
            return $value;
        }
        
        if (self::typeCheck($param, $value)) {
            return $value;
        }
        
        $class = $param->className;
        
        if (is_subclass_of($class, ShouldValidate::class)) {
            $class::dataValidate($request->params);
        }
        
        if (is_subclass_of($class, ShouldMapped::class)) {
            return $class::dataMap($request->params);
        }
        return self::createObjectWithoutConstructor($param, $value);
    }
}
