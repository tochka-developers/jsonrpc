<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterError;

/**
 * @method static JsonRpcInvalidParameterError[] getJsonRpcErrors()
 * @method static bool validate()
 *
 * @see \Tochka\JsonRpc\Support\JsonRpcValidator
 */
class JsonRpcValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
