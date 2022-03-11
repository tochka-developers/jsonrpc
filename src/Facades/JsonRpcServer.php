<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @method static handle(ServerRequestInterface $request, string $serverName = 'default', string $group = null,string $action = null)
 *
 * @see \Tochka\JsonRpc\JsonRpcServer
 */
class JsonRpcServer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
