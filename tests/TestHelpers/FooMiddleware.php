<?php

namespace Tochka\JsonRpc\Tests\TestHelpers;

use Tochka\JsonRpc\Support\OldJsonRpcRequest;

class FooMiddleware
{
    public function handle(OldJsonRpcRequest $request, \Closure $next)
    {
        return $next($request);
    }
}
