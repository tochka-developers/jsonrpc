<?php

namespace Tochka\JsonRpc\Casters;

use BackedEnum;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

class EnumCaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::Enum;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function cast(RouteParam $param, JsonRpcRequest $request): VoidValue|BackedEnum|null
    {
        $value = self::getValue($param, $request->params);
        
        if ($value === null && $param->isNullable) {
            return null;
        }
        
        if (self::optionalCheck($param, $value)) {
            return $value;
        }
        
        /** @var \BackedEnum $className */
        $className = $param->className;
        try {
            return $className::from($value);
        } catch (\ValueError) {
            throw new JsonRpcInvalidParameterException(
                'incorrect_value',
                sprintf(
                    'Invalid value for field. Expected: [%s], Actual: [%s]',
                    implode(',', array_map(fn($enum) => (string)$enum->value, $className::cases())),
                    $value
                ),
                $param->name
            );
        }
    }
}
