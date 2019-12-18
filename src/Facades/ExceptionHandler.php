<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Обработчик исключений JsonRpc
 * @method static handle(\Exception $e)
 *
 * @see \Tochka\JsonRpc\Exceptions\ExceptionHandler
 */
class ExceptionHandler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
