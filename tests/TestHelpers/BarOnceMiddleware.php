<?php

namespace Tochka\JsonRpc\Tests\TestHelpers;

use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;
use Tochka\JsonRpc\Support\OldJsonRpcRequest;

class BarOnceMiddleware implements OnceExecutedMiddleware
{
    public function handle(OldJsonRpcRequest $request, \Closure $next, string $foo, TestClass $class)
    {
        return $next($request);
    }
}
