<?php

namespace Tochka\JsonRpc\Casters;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;

class DICaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::DI;
    }
    
    /**
     * @throws BindingResolutionException
     */
    public static function cast(RouteParam $param, object $input): mixed
    {
        return Container::getInstance()->make($param->className);
    }
}
