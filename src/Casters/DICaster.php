<?php

namespace Tochka\JsonRpc\Casters;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class DICaster extends AbstractPropertyCaster
{
    public static function canCast(RouteParam $param): bool
    {
        return $param->propType === PropType::DI;
    }
    
    /**
     * @throws BindingResolutionException
     */
    public static function cast(RouteParam $param, JsonRpcRequest $request): mixed
    {
        if ($param->className === JsonRpcRequest::class) {
            return $request;
        }
        
        return Container::getInstance()->make($param->className);
    }
}
