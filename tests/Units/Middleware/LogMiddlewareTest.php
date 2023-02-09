<?php

namespace Tochka\JsonRpc\Tests\Units\Middleware;

use Illuminate\Support\Facades\Log;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Middleware\LogMiddleware;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;
use Tochka\JsonRpc\Support\Auth;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Middleware\LogMiddleware
 */
class LogMiddlewareTest extends DefaultTestCase
{
    use MakeRequestTrait;

    private const TEST_CHANNEL = 'test';

    public function setUp(): void
    {
        parent::setUp();

        LogFake::bind();
    }

    public function testHandleJsonRpcRequestWithoutRoute(): void
    {
        $middleware = new LogMiddleware(new Auth(), self::TEST_CHANNEL, [], false);

        $request = $this->makeRequest();

        $response = $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(result: true));

        self::assertTrue($response->result);

        $expectedContext = [
            'request' => [
                'jsonrpc' => '2.0',
                'method' => 'test'
            ],
            'service' => 'guest'
        ];

        $this->assertLog('info', 'New request', $expectedContext);
    }

    public function testHandleJsonRpcRequestMinimalLog(): void
    {
        $middleware = new LogMiddleware(new Auth(), 'test', [], false);

        $route = new JsonRpcRoute('testServer', 'test_method');
        $request = $this->makeRequest($route);

        $response = $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(result: true));

        self::assertTrue($response->result);

        $expectedContext = [
            'request' => [
                'jsonrpc' => '2.0',
                'method' => 'test',
                'params' => []
            ],
            'service' => 'guest',
            'method' => 'test_method',
            'call' => '<NoController>::<NoMethod>',
        ];

        $this->assertLog('info', 'New request', $expectedContext);
    }

    public function testHandleJsonRpcRequestWithAdditional(): void
    {
        $middleware = new LogMiddleware(new Auth(), 'test', [], false);
        $middleware->appendAdditionalContext(['foo' => 'value', 'bar' => 'value']);
        $middleware->appendAdditionalContext(['trace' => 'value1', 'bar' => 'value1']);

        $route = new JsonRpcRoute('testServer', 'test_method');
        $request = $this->makeRequest($route);

        $response = $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(result: true));

        self::assertTrue($response->result);

        $expectedContext = [
            'request' => [
                'jsonrpc' => '2.0',
                'method' => 'test',
                'params' => []
            ],
            'service' => 'guest',
            'method' => 'test_method',
            'call' => '<NoController>::<NoMethod>',
            'foo' => 'value',
            'bar' => 'value1',
            'trace' => 'value1'
        ];

        $this->assertLog('info', 'New request', $expectedContext);
    }

    public function testHandleJsonRpcRequestFullLog(): void
    {
        $middleware = new LogMiddleware(new Auth(), 'test', [], false);

        $route = new JsonRpcRoute('testServer', 'test_method');
        $route->controllerClass = 'TestController';
        $route->controllerMethod = 'test_method';
        $route->group = 'test_group';
        $route->action = 'test_action';
        $request = $this->makeRequest($route);
        $request->getJsonRpcRequest()->params = ['foo' => 'value1', 'bar' => 'value2'];
        $request->getJsonRpcRequest()->id = 'test_id';

        $response = $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(result: true));

        self::assertTrue($response->result);

        $expectedContext = [
            'request' => [
                'id' => 'test_id',
                'jsonrpc' => '2.0',
                'method' => 'test',
                'params' => [
                    'foo' => 'value1',
                    'bar' => 'value2'
                ]
            ],
            'service' => 'guest',
            'method' => 'test_method',
            'call' => 'TestController::test_method',
            'group' => 'test_group',
            'action' => 'test_action',
        ];

        $this->assertLog('info', 'New request', $expectedContext);
    }

    public function testHandleJsonRpcRequestSuccessfulLog(): void
    {
        $middleware = new LogMiddleware(new Auth(), 'test', [], true);

        $route = new JsonRpcRoute('testServer', 'test_method');
        $request = $this->makeRequest($route);

        $expectedResult = ['foo' => 'value1', 'bar' => 'value2'];

        $response = $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(result: $expectedResult));

        self::assertEquals($expectedResult, $response->result);
        self::assertNull($response->error);

        $expectedContext = [
            'request' => [
                'jsonrpc' => '2.0',
                'method' => 'test',
                'params' => []
            ],
            'result' => $expectedResult,
            'service' => 'guest',
            'method' => 'test_method',
            'call' => '<NoController>::<NoMethod>',
        ];

        $this->assertLog('info', 'Successful request', $expectedContext);
    }

    public function testHandleJsonRpcRequestErrorLog(): void
    {
        $middleware = new LogMiddleware(new Auth(), 'test', [], true);

        $route = new JsonRpcRoute('testServer', 'test_method');
        $request = $this->makeRequest($route);

        $expectedError = new JsonRpcError(200, 'Test error');

        $response = $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(error: $expectedError));

        self::assertEquals($expectedError, $response->error);
        self::assertNull($response->result);

        $expectedContext = [
            'request' => [
                'jsonrpc' => '2.0',
                'method' => 'test',
                'params' => []
            ],
            'error' => $expectedError->toArray(),
            'service' => 'guest',
            'method' => 'test_method',
            'call' => '<NoController>::<NoMethod>',
        ];

        $this->assertLog('error', 'Error request', $expectedContext);
    }

    public function testHandleJsonRpcRequestExceptionLog(): void
    {
        $middleware = new LogMiddleware(new Auth(), 'test', [], true);

        $route = new JsonRpcRoute('testServer', 'test_method');
        $request = $this->makeRequest($route);

        $expectedException = new InternalErrorException();

        self::expectException($expectedException::class);
        self::expectExceptionMessage($expectedException->getMessage());
        self::expectExceptionCode($expectedException->getCode());

        $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(exception: $expectedException));

        $expectedContext = [
            'request' => [
                'jsonrpc' => '2.0',
                'method' => 'test',
                'params' => []
            ],
            'error' => $expectedException->getJsonRpcError()->toArray(),
            'service' => 'guest',
            'method' => 'test_method',
            'call' => '<NoController>::<NoMethod>',
        ];

        $this->assertLog('error', 'Error request', $expectedContext);
    }

    public function testHandleJsonRpcRequestHideData(): void
    {
        $rules = [
            '*' => ['secret'],
            'TestController' => ['object.secret', 'secretObject'],
            'FooController' => ['object.bar'],
            'TestController@test_method' => ['object.array.*.secret'],
            'TestController@foo_method' => ['object.array.*.foo'],
        ];

        $middleware = new LogMiddleware(new Auth(), 'test', $rules, false);

        $route = new JsonRpcRoute('testServer', 'test_method');
        $route->controllerClass = 'TestController';
        $route->controllerMethod = 'test_method';

        $request = $this->makeRequest($route);
        $request->getJsonRpcRequest()->params = (object)[
            'foo' => 'value1',
            'secret' => 'secretValue2',
            'object' => [
                'secret' => 'value3',
                'bar' => 'value4',
                'array' => [
                    [
                        'secret' => 'secretValue5',
                        'foo' => 'value6'
                    ],
                    [
                        'secret' => 'secretValue7',
                        'foo' => 'value8'
                    ],
                    [
                        'foo' => 'value7'
                    ]
                ]
            ],
            'secretObject' => [
                'foo' => 'value1',
                'bar' => 'value2'
            ],
        ];

        $response = $middleware->handleJsonRpcRequest($request, $this->getResponseCallback(result: true));

        self::assertTrue($response->result);

        $expectedContext = [
            'request' => [
                'jsonrpc' => '2.0',
                'method' => 'test',
                'params' => [
                    'foo' => 'value1',
                    'secret' => '<hide>',
                    'object' => [
                        'secret' => '<hide>',
                        'bar' => 'value4',
                        'array' => [
                            [
                                'secret' => '<hide>',
                                'foo' => 'value6'
                            ],
                            [
                                'secret' => '<hide>',
                                'foo' => 'value8'
                            ],
                            [
                                'foo' => 'value7'
                            ]
                        ]
                    ],
                    'secretObject' => '<hide>',
                ]
            ],
            'service' => 'guest',
            'method' => 'test_method',
            'call' => 'TestController::test_method',
        ];

        $this->assertLog('info', 'New request', $expectedContext);
    }

    private function assertLog(string $level, string $message, array $context): void
    {
        Log::channel(self::TEST_CHANNEL)
            ->assertLogged(
                fn (LogEntry $log) => $log->level === $level
                && $log->message === $message
                && $log->context == $context
            );
    }

    private function getResponseCallback(
        mixed $result = null,
        ?JsonRpcError $error = null,
        ?\Throwable $exception = null
    ): callable
    {
        return function (JsonRpcServerRequest $request) use ($result, $error, $exception): JsonRpcResponse {
            if ($exception !== null) {
                throw $exception;
            }
            return new JsonRpcResponse($request->getJsonRpcRequest()->id, $result, $error);
        };
    }
}
