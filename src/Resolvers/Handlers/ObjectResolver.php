<?php

namespace Tochka\JsonRpc\Resolvers\Handlers;

use Illuminate\Validation\ValidationException;
use Tochka\JsonRpc\Contracts\ShouldMapped;
use Tochka\JsonRpc\Contracts\ShouldValidated;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class ObjectResolver extends AbstractResolver
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::Object;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     * @throws \ReflectionException
     * @throws ValidationException
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
        
        $class = $param->className;
        
        if (is_subclass_of($class, ShouldValidated::class)) {
            self::doValidation($class::rules(), $value);
        }
        
        if (is_subclass_of($class, ShouldMapped::class)) {
            return $class::dataMap($request->params);
        }
        
        return self::createObjectWithoutConstructor($param, $value);
    }
}
