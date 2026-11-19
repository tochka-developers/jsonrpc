<?php

namespace Tochka\JsonRpc\Tests\Resolvers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParametersException;
use Tochka\JsonRpc\Resolvers\ParamsResolver;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\RouteParser;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Tests\Router\Examples\ParamsTestController;
use Tochka\JsonRpc\Tests\TestParams\MappedRequestObject;
use Tochka\JsonRpc\Tests\TestParams\RequestChildObject;
use Tochka\JsonRpc\Tests\TestParams\RequestObject;

#[CoversClass(ParamsResolver::class)]
class ParamsResolverTest extends TestCase
{
    /**
     * Verify root params populate the DTO alongside an injected request.
     */
    #[DataProvider('providerParams')]
    public function testRequestObject(array|object $params): void
    {
        $request = $this->makeRequest($params);
        $result = $this->resolve('apiParams', $request);

        $this->assertInstanceOf(RequestObject::class, $result['one']);
        $this->assertSame('value', $result['one']->string);
        $this->assertSame('other value', $result['one']->otherString);
        $this->assertInstanceOf(RequestChildObject::class, $result['one']->data);
        $this->assertTrue($result['one']->data->bool);
        $this->assertFalse($result['one']->data->otherBool);
        $this->assertSame($request, $result['request']);
    }

    /**
     * @return array<string, array{0: array|object}>
     */
    public static function providerParams(): array
    {
        $params = [
            'string' => 'value',
            'otherString' => 'other value',
            'data' => (object)['bool' => true, 'otherBool' => false],
        ];

        return [
            'object' => [(object)$params],
            'array' => [$params],
            'argument name collision' => [(object)[
                ...$params, 'one' => (object)['string' => 'incorrect nested value'],
            ]],
        ];
    }

    /**
     * Verify absent, null and empty nested data retain property defaults.
     */
    #[DataProvider('providerOptionalData')]
    public function testOptionalData(object $params, bool $hasData): void
    {
        $result = $this->resolve('apiParams', $this->makeRequest($params));
        $data = $result['one']->data;

        if ($hasData) {
            $this->assertInstanceOf(RequestChildObject::class, $data);
            $this->assertNull($data->bool);
            $this->assertNull($data->otherBool);
        } else {
            $this->assertNull($data);
        }
    }

    /**
     * @return array<string, array{0: object, 1: bool}>
     */
    public static function providerOptionalData(): array
    {
        $params = ['string' => 'string', 'otherString' => 'other value'];

        return [
            'absent' => [(object)$params, false],
            'null' => [(object)[...$params, 'data' => null], false],
            'empty object' => [(object)[...$params, 'data' => (object)[]], true],
        ];
    }

    /**
     * Verify invalid root and nested fields retain their validation errors.
     */
    #[DataProvider('providerInvalidParams')]
    public function testInvalidParams(object $params, string $code, string $field): void
    {
        try {
            $this->resolve('apiParams', $this->makeRequest($params));
            $this->fail('Expected invalid parameters.');
        } catch (JsonRpcInvalidParametersException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame($code, $errors[0]->code);
            $this->assertSame($field, $errors[0]->fieldName);
        }
    }

    /**
     * @return array<string, array{0: object, 1: string, 2: string}>
     */
    public static function providerInvalidParams(): array
    {
        return [
            'missing string' => [(object)['otherString' => 'other value'], 'required', 'string'],
            'wrong string type' => [(object)['string' => 123, 'otherString' => 'other value'], 'type', 'string'],
            'null string' => [(object)['string' => null, 'otherString' => 'other value'], 'not_nullable', 'string'],
            'wrong data type' => [(object)['string' => 'string', 'otherString' => 'other value', 'data' => false], 'type', 'data'],
            'wrong nested type' => [(object)[
                'string' => 'string', 'otherString' => 'other value',
                'data' => (object)['bool' => 'yes'],
            ], 'type', 'bool'],
        ];
    }

    /**
     * Verify ordinary objects still resolve from the named parameter.
     */
    public function testOrdinaryObject(): void
    {
        $params = (object)['one' => (object)['string' => 'nested string', 'otherString' => 'other value']];
        $result = $this->resolve('apiObject', $this->makeRequest($params));

        $this->assertInstanceOf(RequestObject::class, $result['one']);
        $this->assertSame('nested string', $result['one']->string);
    }

    /**
     * Verify custom request mappers receive the complete original input.
     */
    #[DataProvider('providerParams')]
    public function testCustomDataMap(array|object $params): void
    {
        $result = $this->resolve('apiMappedParams', $this->makeRequest($params));

        $this->assertInstanceOf(MappedRequestObject::class, $result['one']);
        $this->assertSame($params, $result['one']->input);
    }

    /**
     * Build a JSON-RPC request around the supplied params.
     */
    private function makeRequest(array|object $params): JsonRpcRequest
    {
        return new JsonRpcRequest((object)[
            'jsonrpc' => '2.0', 'id' => 'test-id', 'method' => 'test', 'params' => $params,
        ]);
    }

    /**
     * Parse controller attributes before resolving arguments.
     */
    private function resolve(string $method, JsonRpcRequest $request): array
    {
        $route = new Route('test', ParamsTestController::class, $method);
        $parser = new RouteParser($route);
        $parser->fillRoute(new ReflectionMethod(ParamsTestController::class, $method));

        return (new ParamsResolver($request))->resolve($route->getParams(), $request->params);
    }
}
