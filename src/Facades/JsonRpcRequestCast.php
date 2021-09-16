<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;

/**
 * @method static mixed cast(string $casterName, string $className, $value, string $fieldName)
 * @method static addCaster(GlobalCustomCasterInterface $caster)
 * @method static getCasterForClass(string $className)
 * @see \Tochka\JsonRpc\Support\JsonRpcRequestCast
 */
class JsonRpcRequestCast extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
