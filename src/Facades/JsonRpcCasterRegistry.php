<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\CasterRegistryInterface;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Route\Parameters\Parameter;

/**
 * @psalm-api
 *
 * @method static void addCaster(GlobalCustomCasterInterface $caster)
 * @method static string|null getCasterForClass(string $className)
 * @method static object|null cast(string $casterName, Parameter $parameter, mixed $value, string $fieldName)
 *
 * @see CasterRegistryInterface
 * @see \Tochka\JsonRpc\Support\CasterRegistry
 */
class JsonRpcCasterRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CasterRegistryInterface::class;
    }
}
