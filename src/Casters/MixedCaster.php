<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;

class MixedCaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::Mixed;
    }
    
    public static function cast(RouteParam $param, object $input): mixed
    {
        // todo проверить на пустоту и на null
        return self::getValue($param, $input);
    }
}
