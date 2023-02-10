<?php

namespace Tochka\JsonRpc\Tests\Units;

use Hamcrest\Type\IsCallable;
use Illuminate\Container\Container;
use Mockery\Mock;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\ExceptionHandlerInterface;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\HttpRequestMiddlewareInterface;
use Tochka\JsonRpc\Contracts\JsonRpcRequestMiddlewareInterface;
use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;
use Tochka\JsonRpc\Contracts\ParserInterface;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\JsonRpcServer;
use Tochka\JsonRpc\Standard\Contracts\JsonRpcExceptionInterface;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Standard\Exceptions\MethodNotFoundException;

/**
 * @covers \Tochka\JsonRpc\JsonRpcServer
 */
class JsonRpcServerTest extends DefaultTestCase
{
    public function testHandleRequestWithResponse(): void
    {
        $expectedServerName = 'testServer';
        $expectedMethod = 'testMethod';
        $expectedGroup = 'testGroup';
        $expectedAction = 'testAction';
        $expectedId = 'testId';
        $expectedParams = ['foo' => 'bar'];

        $expectedRequest = new JsonRpcServerRequest(
            \Mockery::mock(ServerRequestInterface::class),
            new JsonRpcRequest($expectedMethod, $expectedParams, $expectedId)
        );

        $middleware = $this->makeJsonRpcMiddlewareMock($expectedRequest);

        $server = new JsonRpcServer(
            \Mockery::mock(ParserInterface::class),
            $this->makeResolverMock($expectedRequest),
            \Mockery::mock(ExceptionHandlerInterface::class),
            $this->makeMiddlewareRegistryMock($expectedServerName, JsonRpcRequestMiddlewareInterface::class, [$middleware]),
            $this->makeRouterMock(
                $expectedServerName,
                $expectedMethod,
                $expectedGroup,
                $expectedAction,
                new JsonRpcRoute(
                    $expectedServerName,
                    $expectedMethod,
                    $expectedGroup,
                    $expectedAction
                )
            ),
            Container::getInstance()
        );

        $expectedResponse = new JsonRpcResponse($expectedId, true);

        $actualResponse = $server->handleRequest(
            $expectedRequest,
            $expectedServerName,
            $expectedGroup,
            $expectedAction
        );

        self::assertEquals($expectedResponse, $actualResponse);
    }

    public function testHandleRequestNoResponse(): void
    {
        $expectedServerName = 'testServer';
        $expectedMethod = 'testMethod';
        $expectedGroup = 'testGroup';
        $expectedAction = 'testAction';
        $expectedId = null;
        $expectedParams = ['foo' => 'bar'];

        $expectedRequest = new JsonRpcServerRequest(
            \Mockery::mock(ServerRequestInterface::class),
            new JsonRpcRequest($expectedMethod, $expectedParams, $expectedId)
        );

        $middleware = $this->makeJsonRpcMiddlewareMock($expectedRequest);

        $server = new JsonRpcServer(
            \Mockery::mock(ParserInterface::class),
            $this->makeResolverMock($expectedRequest),
            \Mockery::mock(ExceptionHandlerInterface::class),
            $this->makeMiddlewareRegistryMock($expectedServerName, JsonRpcRequestMiddlewareInterface::class, [$middleware]),
            $this->makeRouterMock(
                $expectedServerName,
                $expectedMethod,
                $expectedGroup,
                $expectedAction,
                new JsonRpcRoute(
                    $expectedServerName,
                    $expectedMethod,
                    $expectedGroup,
                    $expectedAction
                )
            ),
            Container::getInstance()
        );

        $actualResponse = $server->handleRequest(
            $expectedRequest,
            $expectedServerName,
            $expectedGroup,
            $expectedAction
        );

        self::assertNull($actualResponse);
    }

    public function testHandleRequestMethodNotFound(): void
    {
        $expectedServerName = 'testServer';
        $expectedMethod = 'testMethod';
        $expectedGroup = 'testGroup';
        $expectedAction = 'testAction';
        $expectedId = null;
        $expectedParams = ['foo' => 'bar'];

        $expectedRequest = new JsonRpcServerRequest(
            \Mockery::mock(ServerRequestInterface::class),
            new JsonRpcRequest($expectedMethod, $expectedParams, $expectedId)
        );

        $exception = new MethodNotFoundException();

        $server = new JsonRpcServer(
            \Mockery::mock(ParserInterface::class),
            \Mockery::mock(HandleResolverInterface::class),
            $this->makeExceptionHandlerMock($exception),
            \Mockery::mock(MiddlewareRegistryInterface::class),
            $this->makeRouterMock(
                $expectedServerName,
                $expectedMethod,
                $expectedGroup,
                $expectedAction,
                null
            ),
            Container::getInstance()
        );

        $actualResponse = $server->handleRequest(
            $expectedRequest,
            $expectedServerName,
            $expectedGroup,
            $expectedAction
        );

        $expectedResponse = new JsonRpcResponse($expectedId, error: $exception->getJsonRpcError());

        self::assertEquals($expectedResponse, $actualResponse);
    }

    public function testHandleSuccessful(): void
    {
        $expectedServerName = 'testServer';
        $expectedGroup = 'testGroup';
        $expectedAction = 'testAction';
        $expectedMethod = 'testMethod';
        $expectedId = 'testId';

        $expectedRequest = new JsonRpcServerRequest(
            \Mockery::mock(ServerRequestInterface::class),
            new JsonRpcRequest($expectedMethod, id: $expectedId)
        );

        $middleware = \Mockery::mock(HttpRequestMiddlewareInterface::class);
        $middleware->shouldReceive('handleHttpRequest')
            ->once()
            ->with($expectedRequest->getServerRequest(), IsCallable::callableValue())
            ->andReturnUsing(fn (ServerRequestInterface $request, callable $next) => $next($request));

        /** @var JsonRpcServer|Mock $serverMock */
        $serverMock = \Mockery::mock(JsonRpcServer::class, [
            $this->makeParserMock($expectedRequest->getServerRequest(), $expectedRequest),
            \Mockery::mock(HandleResolverInterface::class),
            \Mockery::mock(ExceptionHandlerInterface::class),
            $this->makeMiddlewareRegistryMock($expectedServerName, HttpRequestMiddlewareInterface::class, [$middleware]),
            \Mockery::mock(RouterInterface::class),
            Container::getInstance()
        ])->makePartial();

        $expectedResponse = new JsonRpcResponse($expectedId, true);
        $expectedResponses = new JsonRpcResponseCollection();
        $expectedResponses->add($expectedResponse);

        $serverMock->shouldReceive('handleRequest')
            ->once()
            ->with($expectedRequest, $expectedServerName, $expectedGroup, $expectedAction)
            ->andReturn($expectedResponse);

        $actualResponses = $serverMock->handle($expectedRequest->getServerRequest(), $expectedServerName, $expectedGroup, $expectedAction);

        self::assertEquals($expectedResponses, $actualResponses);
    }

    public function testHandleException(): void
    {
        $expectedServerName = 'testServer';
        $expectedGroup = 'testGroup';
        $expectedAction = 'testAction';
        $expectedMethod = 'testMethod';
        $expectedId = 'testId';

        $expectedRequest = new JsonRpcServerRequest(
            \Mockery::mock(ServerRequestInterface::class),
            new JsonRpcRequest($expectedMethod, id: $expectedId)
        );

        $middleware = \Mockery::mock(HttpRequestMiddlewareInterface::class);
        $middleware->shouldReceive('handleHttpRequest')
            ->once()
            ->with($expectedRequest->getServerRequest(), IsCallable::callableValue())
            ->andReturnUsing(function () {
                throw new MethodNotFoundException();
            });

        $exception = new MethodNotFoundException();

        /** @var JsonRpcServer|Mock $serverMock */
        $serverMock = \Mockery::mock(JsonRpcServer::class, [
            \Mockery::mock(ParserInterface::class),
            \Mockery::mock(HandleResolverInterface::class),
            $this->makeExceptionHandlerMock($exception),
            $this->makeMiddlewareRegistryMock($expectedServerName, HttpRequestMiddlewareInterface::class, [$middleware]),
            \Mockery::mock(RouterInterface::class),
            Container::getInstance()
        ])->makePartial();

        $expectedResponse = new JsonRpcResponse(null, error: $exception->getJsonRpcError());
        $expectedResponses = new JsonRpcResponseCollection();
        $expectedResponses->add($expectedResponse);

        $serverMock->shouldReceive('handleRequest')
            ->never();

        $actualResponses = $serverMock->handle($expectedRequest->getServerRequest(), $expectedServerName, $expectedGroup, $expectedAction);

        self::assertEquals($expectedResponses, $actualResponses);
    }

    private function makeResolverMock(JsonRpcServerRequest $expectedRequest): HandleResolverInterface|Mock
    {
        $resolver = \Mockery::mock(HandleResolverInterface::class);
        $resolver->shouldReceive('handle')
            ->once()
            ->with($expectedRequest)
            ->andReturn(true);

        return $resolver;
    }

    private function makeJsonRpcMiddlewareMock(
        JsonRpcServerRequest $expectedRequest
    ): JsonRpcRequestMiddlewareInterface|Mock {
        $middleware = \Mockery::mock(JsonRpcRequestMiddlewareInterface::class);
        $middleware->shouldReceive('handleJsonRpcRequest')
            ->once()
            ->with($expectedRequest, IsCallable::callableValue())
            ->andReturnUsing(fn (JsonRpcServerRequest $request, callable $next) => $next($request));

        return $middleware;
    }

    private function makeMiddlewareRegistryMock(
        string $expectedServerName,
        string $middlewareInterface,
        array $middleware = []
    ): MiddlewareRegistryInterface|Mock {
        $middlewareRegistry = \Mockery::mock(MiddlewareRegistryInterface::class);
        $middlewareRegistry->shouldReceive('getMiddleware')
            ->once()
            ->with($expectedServerName, $middlewareInterface)
            ->andReturn($middleware);

        return $middlewareRegistry;
    }

    private function makeRouterMock(
        string $expectedServerName,
        string $expectedMethod,
        string $expectedGroup,
        string $expectedAction,
        ?JsonRpcRoute $expectedRoute
    ): RouterInterface|Mock {
        $router = \Mockery::mock(RouterInterface::class);
        $router->shouldReceive('get')
            ->once()
            ->with($expectedServerName, $expectedMethod, $expectedGroup, $expectedAction)
            ->andReturn($expectedRoute);

        return $router;
    }

    private function makeExceptionHandlerMock(JsonRpcExceptionInterface $exception): ExceptionHandlerInterface|Mock
    {
        $handler = \Mockery::mock(ExceptionHandlerInterface::class);
        $handler->shouldReceive('handle')
            ->once()
            ->with(\Mockery::type($exception::class))
            ->andReturn($exception->getJsonRpcError());

        return $handler;
    }

    private function makeParserMock(
        ServerRequestInterface $expectedServerRequest,
        JsonRpcServerRequest $expectedJsonRpcRequest
    ): ParserInterface|Mock {
        $parser = \Mockery::mock(ParserInterface::class);
        $parser->shouldReceive('parse')
            ->once()
            ->with($expectedServerRequest)
            ->andReturn([$expectedJsonRpcRequest]);

        return $parser;
    }
}
