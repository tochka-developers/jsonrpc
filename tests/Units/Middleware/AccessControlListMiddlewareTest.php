<?php

namespace Tochka\JsonRpc\Tests\Units\Middleware;

use Tochka\JsonRpc\DTO\JsonRpcClient;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Middleware\AccessControlListMiddleware;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\ForbiddenException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\UnauthorizedException;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\MethodNotFoundException;
use Tochka\JsonRpc\Support\Auth;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Middleware\AccessControlListMiddleware
 */
class AccessControlListMiddlewareTest extends DefaultTestCase
{
    use MakeRequestTrait;

    public function testHandleJsonRpcRequestNoRoute(): void
    {
        $auth = new Auth();
        $middleware = new AccessControlListMiddleware($auth, []);

        $request = $this->makeRequest();

        self::expectException(MethodNotFoundException::class);
        self::expectExceptionMessage(JsonRpcException::MESSAGE_METHOD_NOT_FOUND);

        $middleware->handleJsonRpcRequest($request, function () {
        });
    }

    public function testHandleJsonRpcRequestEmptyRules(): void
    {
        $auth = new Auth();
        $middleware = new AccessControlListMiddleware($auth, []);

        $request = $this->makeRequest(new JsonRpcRoute('foo', 'test'));

        self::expectException(ForbiddenException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_FORBIDDEN);

        $middleware->handleJsonRpcRequest($request, function () {
        });
    }

    public function testHandleJsonRpcRequestMethodRulesAccess(): void
    {
        $testClient = 'test';

        $auth = new Auth();
        $auth->setClient(new JsonRpcClient($testClient));

        $middleware = new AccessControlListMiddleware($auth, [
            '*' => 'foo',
            'TestController' => 'bar',
            'TestController@test_method' => $testClient,
            'FooController' => 'foo',
            'FooController@test_method' => 'foo',
        ]);

        $request = $this->makeRequest($this->makeRoute());

        $response = $middleware->handleJsonRpcRequest(
            $request,
            function (JsonRpcServerRequest $request): JsonRpcResponse {
                return new JsonRpcResponse($request->getJsonRpcRequest()->id, true);
            }
        );

        self::assertTrue($response->result);
    }

    public function testHandleJsonRpcRequestMethodRulesForbidden(): void
    {
        $testClient = 'test';

        $auth = new Auth();
        $auth->setClient(new JsonRpcClient($testClient));

        $middleware = new AccessControlListMiddleware($auth, [
            '*' => 'foo',
            'TestController' => 'bar',
            'TestController@test_method' => 'bar',
            'FooController' => 'foo',
            'FooController@test_method' => 'foo',
        ]);

        $request = $this->makeRequest($this->makeRoute());

        self::expectException(ForbiddenException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_FORBIDDEN);

        $middleware->handleJsonRpcRequest($request, function () {
        });
    }

    public function testHandleJsonRpcRequestControllerRulesAccess(): void
    {
        $testClient = 'test';

        $auth = new Auth();
        $auth->setClient(new JsonRpcClient($testClient));

        $middleware = new AccessControlListMiddleware($auth, [
            '*' => 'foo',
            'TestController' => $testClient,
            'FooController' => 'foo',
            'FooController@test_method' => 'foo',
        ]);

        $request = $this->makeRequest($this->makeRoute());

        $response = $middleware->handleJsonRpcRequest(
            $request,
            function (JsonRpcServerRequest $request): JsonRpcResponse {
                return new JsonRpcResponse($request->getJsonRpcRequest()->id, true);
            }
        );

        self::assertTrue($response->result);
    }

    public function testHandleJsonRpcRequestControllerRulesForbidden(): void
    {
        $testClient = 'test';

        $auth = new Auth();
        $auth->setClient(new JsonRpcClient($testClient));

        $middleware = new AccessControlListMiddleware($auth, [
            '*' => 'foo',
            'TestController' => 'foo',
            'FooController' => 'foo',
            'FooController@test_method' => 'foo',
        ]);

        $request = $this->makeRequest($this->makeRoute());

        self::expectException(ForbiddenException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_FORBIDDEN);

        $middleware->handleJsonRpcRequest($request, function () {
        });
    }

    public function testHandleJsonRpcRequestGlobalRulesAccess(): void
    {
        $testClient = 'test';

        $auth = new Auth();
        $auth->setClient(new JsonRpcClient($testClient));

        $middleware = new AccessControlListMiddleware($auth, [
            '*' => $testClient,
            'FooController' => 'foo',
            'FooController@test_method' => 'foo',
        ]);

        $request = $this->makeRequest($this->makeRoute());

        $response = $middleware->handleJsonRpcRequest(
            $request,
            function (JsonRpcServerRequest $request): JsonRpcResponse {
                return new JsonRpcResponse($request->getJsonRpcRequest()->id, true);
            }
        );

        self::assertTrue($response->result);
    }

    public function testHandleJsonRpcRequestGlobalRulesForbidden(): void
    {
        $testClient = 'test';

        $auth = new Auth();
        $auth->setClient(new JsonRpcClient($testClient));

        $middleware = new AccessControlListMiddleware($auth, [
            '*' => 'foo',
            'FooController' => 'foo',
            'FooController@test_method' => 'foo',
        ]);

        $request = $this->makeRequest($this->makeRoute());

        self::expectException(ForbiddenException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_FORBIDDEN);

        $middleware->handleJsonRpcRequest($request, function () {
        });
    }

    public function testHandleJsonRpcRequestGuest(): void
    {
        $auth = new Auth();

        $middleware = new AccessControlListMiddleware($auth, [
            '*' => 'foo',
            'FooController' => 'foo',
            'FooController@test_method' => 'foo',
        ]);

        $request = $this->makeRequest($this->makeRoute());

        self::expectException(UnauthorizedException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_UNAUTHORIZED);

        $middleware->handleJsonRpcRequest($request, function () {
        });
    }

    private function makeRoute(): JsonRpcRoute
    {
        $route = new JsonRpcRoute('foo', 'test');
        $route->controllerClass = 'TestController';
        $route->controllerMethod = 'test_method';

        return $route;
    }
}
