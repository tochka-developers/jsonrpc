<?php

namespace Tochka\JsonRpc\Tests\Units\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;

trait MakeRequestTrait
{
    private function makeRequest(?JsonRpcRoute $route = null): JsonRpcServerRequest
    {
        $request = new JsonRpcServerRequest(
            \Mockery::mock(ServerRequestInterface::class),
            new JsonRpcRequest('test')
        );

        $request->setRoute($route);

        return $request;
    }
}
