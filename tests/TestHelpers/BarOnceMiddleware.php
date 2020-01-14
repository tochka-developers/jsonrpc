<?php

namespace Tochka\JsonRpc\Tests\TestHelpers;

use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class BarOnceMiddleware implements OnceExecutedMiddleware
{
    public function handle(JsonRpcRequest $request, \Closure $next, string $foo, TestClass $class)
    {
        return $next($request);
    }
}
