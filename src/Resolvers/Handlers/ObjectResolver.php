<?php

namespace Tochka\JsonRpc\Resolvers\Handlers;

use Illuminate\Validation\ValidationException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Traits\WithDataMap;
use Tochka\JsonRpc\Traits\WithValidation;

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
        $uses = class_uses_recursive($class);
        if (\in_array(WithValidation::class, $uses)) {
            /** @var WithValidation $class */
            $rules = $class::rules();
            if (count($rules) > 0) {
                self::doValidation($value, $rules, $class::messages(), $class::attributes());
            }
        }
        
        if(\in_array(WithDataMap::class, $uses)) {
            /** @var WithDataMap $class */
            return $class::dataMap($param, $value);
        }
        
        return self::createObjectWithoutConstructor($param, $value);
    }
}
