<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * JsonRpcHandler Facade
 * @method static handle(\Exception $e)
 */
class JsonRpcHandler extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'JsonRpcHandler';
    }
}
