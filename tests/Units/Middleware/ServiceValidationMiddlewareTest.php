<?php

namespace Tochka\JsonRpc\Tests\Units\Middleware;

use Illuminate\Container\Container;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\Middleware\ServiceValidationMiddleware;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\ForbiddenException;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Middleware\ServiceValidationMiddleware
 */
class ServiceValidationMiddlewareTest extends DefaultTestCase
{
    use MakeRequestTrait;

    public function testHandleHttpRequestEmptyRules(): void
    {
        $middleware = new ServiceValidationMiddleware();

        self::expectException(ForbiddenException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_FORBIDDEN);

        $middleware->handleHttpRequest(\Mockery::mock(ServerRequestInterface::class), function () {
        });
    }

    public function testHandleHttpRequestAccessAll(): void
    {
        $middleware = new ServiceValidationMiddleware('*');

        $expectedResult = new JsonRpcResponseCollection();
        $expectedResult->add(new JsonRpcResponse(result: true));

        $result = $middleware->handleHttpRequest(\Mockery::mock(ServerRequestInterface::class), fn () => $expectedResult
        );

        self::assertEquals($expectedResult, $result);
    }

    public function testHandleHttpRequestRulesNotArray(): void
    {
        $middleware = new ServiceValidationMiddleware(true);

        self::expectException(ForbiddenException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_FORBIDDEN);

        $middleware->handleHttpRequest(\Mockery::mock(ServerRequestInterface::class), function () {
        });
    }

    public function testHandleHttpRequestAccessAllArray(): void
    {
        $middleware = new ServiceValidationMiddleware(['*']);

        $expectedResult = new JsonRpcResponseCollection();
        $expectedResult->add(new JsonRpcResponse(result: true));

        $result = $middleware->handleHttpRequest(\Mockery::mock(ServerRequestInterface::class), fn () => $expectedResult
        );

        self::assertEquals($expectedResult, $result);
    }

    public function testHandleHttpRequestAccess(): void
    {
        $middleware = new ServiceValidationMiddleware(['192.168.0.2', '192.168.0.1']);

        $expectedResult = new JsonRpcResponseCollection();
        $expectedResult->add(new JsonRpcResponse(result: true));

        $this->mockRequestIp('192.168.0.1');

        $result = $middleware->handleHttpRequest(\Mockery::mock(ServerRequestInterface::class), fn () => $expectedResult
        );

        self::assertEquals($expectedResult, $result);
    }

    public function testHandleHttpRequestForbidden(): void
    {
        $middleware = new ServiceValidationMiddleware(['192.168.0.2', '192.168.0.3']);

        $expectedResult = new JsonRpcResponseCollection();
        $expectedResult->add(new JsonRpcResponse(result: true));

        $this->mockRequestIp('192.168.0.1');

        self::expectException(ForbiddenException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_FORBIDDEN);

        $middleware->handleHttpRequest(\Mockery::mock(ServerRequestInterface::class), fn () => $expectedResult);
    }

    private function mockRequestIp(string $ip): void
    {
        $container = Container::getInstance();
        $container->singleton('request', function () use ($ip) {
            $request = \Mockery::mock();
            $request->shouldReceive('ip')
                ->once()
                ->andReturn($ip);

            return $request;
        });
    }
}
