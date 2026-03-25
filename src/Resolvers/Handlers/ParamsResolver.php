<?php

namespace Tochka\JsonRpc\Resolvers\Handlers;

use Illuminate\Validation\ValidationException;
use Tochka\JsonRpc\Contracts\ShouldMapped;
use Tochka\JsonRpc\Contracts\ShouldValidated;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class ParamsResolver extends AbstractResolver
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::RequestObject;
    }
    
    /**
     * @throws \ReflectionException
     * @throws JsonRpcInvalidParameterException
     * @throws ValidationException
     */
    public static function cast(RouteParam $param, JsonRpcRequest $request): mixed
    {
        $class = $param->className;
        
        if (is_subclass_of($class, ShouldValidated::class)) {
            self::doValidation($class::rules(), $request->params);
        }
        
        if (is_subclass_of($class, ShouldMapped::class)) {
            return $class::dataMap($request->params);
        }
        
        return self::createObjectWithoutConstructor($param, $request->params);
    }
}
