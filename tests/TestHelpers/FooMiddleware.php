<?php

namespace Tochka\JsonRpc\Tests\TestHelpers;

use Tochka\JsonRpc\Support\JsonRpcRequest;

class FooMiddleware
{
    public function handle(JsonRpcRequest $request, \Closure $next)
    {
        return $next($request);
    }
}
