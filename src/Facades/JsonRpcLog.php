<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;

/**
* LogFacade Facade
* @method static emergency(string $message, array $context = [])
* @method static alert(string $message, array $context = [])
* @method static critical(string $message, array $context = [])
* @method static error(string $message, array $context = [])
* @method static warning(string $message, array $context = [])
* @method static notice(string $message, array $context = [])
* @method static info(string $message, array $context = [])
* @method static debug(string $message, array $context = [])
* @method static log(string $message, array $context = [])
*/
class JsonRpcLog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'JsonRpcLog';
    }
}
