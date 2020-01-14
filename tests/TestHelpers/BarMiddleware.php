<?php

namespace Tochka\JsonRpc\Tests\TestHelpers;

use Tochka\JsonRpc\Support\JsonRpcRequest;

class BarMiddleware
{
    public function handle(JsonRpcRequest $request, \Closure $next, string $foo, int $bar = 123)
    {
        return $next($request);
    }
}
