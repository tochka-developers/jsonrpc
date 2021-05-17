<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\GlobalPropertyCasterInterface;

/**
 * @method static mixed cast(string $className, object $params)
 * @method static addCaster(GlobalPropertyCasterInterface $caster)
 * @see \Tochka\JsonRpc\Support\JsonRpcRequestCast
 */
class JsonRpcRequestCast extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
