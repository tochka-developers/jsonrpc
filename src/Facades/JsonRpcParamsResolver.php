<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Route\CacheParamsResolver;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;
use Tochka\JsonRpc\Route\ParamsResolver;

/**
 * @method static Parameter[] resolveParameters(\ReflectionMethod $reflectionMethod)
 * @method static Parameter resolveResult(\ReflectionMethod $reflectionMethod)
 * @method static ParameterObject[] getClasses()
 * @method static ParameterObject|null getParameterObject(string $className)
 * @see ParamsResolver
 * @see CacheParamsResolver
 * @see ParamsResolverInterface
 */
class JsonRpcParamsResolver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ParamsResolverInterface::class;
    }
}
