<?php

namespace Tochka\JsonRpc\Tests\Units\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcClient;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\Middleware\TokenAuthMiddleware;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Support\Auth;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Middleware\TokenAuthMiddleware
 */
class TokenAuthMiddlewareTest extends DefaultTestCase
{
    private Auth $auth;
    private TokenAuthMiddleware $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = new Auth();
        $this->middleware = new TokenAuthMiddleware(
            $this->auth,
            'Test-Header',
            ['service1' => 'token1', 'service2' => 'token2']
        );
    }

    public function testHandleHttpRequestNoHeader(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')
            ->once()
            ->with('Test-Header')
            ->andReturnNull();

        $expectedResponse = new JsonRpcResponseCollection();
        $expectedResponse->add(new JsonRpcResponse(result: true));

        $response = $this->middleware->handleHttpRequest($request, fn() => $expectedResponse);

        self::assertEquals($expectedResponse, $response);
        self::assertEquals(JsonRpcClient::GUEST, $this->auth->getClient()->getName());
    }

    public function testHandleHttpRequestIncorrectToken(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')
            ->once()
            ->with('Test-Header')
            ->andReturn('token3');

        $expectedResponse = new JsonRpcResponseCollection();
        $expectedResponse->add(new JsonRpcResponse(result: true));

        $response = $this->middleware->handleHttpRequest($request, fn() => $expectedResponse);

        self::assertEquals($expectedResponse, $response);
        self::assertEquals(JsonRpcClient::GUEST, $this->auth->getClient()->getName());
    }

    public function testHandleHttpRequestSuccessAuth(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')
            ->once()
            ->with('Test-Header')
            ->andReturn('token2');

        $expectedResponse = new JsonRpcResponseCollection();
        $expectedResponse->add(new JsonRpcResponse(result: true));

        $response = $this->middleware->handleHttpRequest($request, fn() => $expectedResponse);

        self::assertEquals($expectedResponse, $response);
        self::assertEquals('service2', $this->auth->getClient()->getName());
    }
}
