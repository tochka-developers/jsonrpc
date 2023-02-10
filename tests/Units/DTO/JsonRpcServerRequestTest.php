<?php

namespace Tochka\JsonRpc\Tests\Units\DTO;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\DTO\JsonRpcServerRequest
 */
class JsonRpcServerRequestTest extends DefaultTestCase
{
    public function testGettersSetters(): void
    {
        $expectedServerRequest = \Mockery::mock(ServerRequestInterface::class);
        $expectedJsonRpcRequest = new JsonRpcRequest('test');
        $expectedRoute = new JsonRpcRoute('testServer', 'testMethod');

        $request = new JsonRpcServerRequest($expectedServerRequest, $expectedJsonRpcRequest);
        $request->setRoute($expectedRoute);

        self::assertEquals($expectedServerRequest, $request->getServerRequest());
        self::assertEquals($expectedJsonRpcRequest, $request->getJsonRpcRequest());
        self::assertEquals($expectedRoute, $request->getRoute());
    }
}
