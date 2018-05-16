<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Exceptions\JsonRpcHandler as Handler;

/**
 * JsonRpcHandler Facade
 * @method static handle(\Exception $e)
 */
class JsonRpcHandler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Handler::class;
    }
}
