<?php

namespace Tochka\JsonRpc\Resolvers\Handlers;

use Illuminate\Validation\ValidationException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Traits\WithDataMap;
use Tochka\JsonRpc\Traits\WithValidation;

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
        
        $uses = class_uses_recursive($class);
        
        if (in_array(WithValidation::class, $uses)) {
            /** @var WithValidation $class */
            $rules = $class::rules();
            if (count($rules) > 0) {
                self::doValidation($request->params, $rules, $class::messages(), $class::attributes());
            }
        }
        
        if(\in_array(WithDataMap::class, $uses)) {
            /** @var WithDataMap $class */
            return $class::dataMap($param, $request->params);
        }
        
        return self::createObjectWithoutConstructor($param, $request->params);
    }
}
