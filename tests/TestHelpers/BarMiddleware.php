<?php

namespace Tochka\JsonRpc\Tests\TestHelpers;

use Tochka\JsonRpc\Support\OldJsonRpcRequest;

class BarMiddleware
{
    public function handle(OldJsonRpcRequest $request, \Closure $next, string $foo, int $bar = 123)
    {
        return $next($request);
    }
}
