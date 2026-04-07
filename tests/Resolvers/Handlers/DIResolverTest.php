<?php

namespace Tochka\JsonRpc\Tests\Resolvers\Handlers;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Resolvers\Handlers\DIResolver;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Tests\TestParams\DIObject;

#[CoversClass(DIResolver::class)]
class DIResolverTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return ResolverTestHelper::canCastCases(PropType::DI);
    }
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = DIResolver::canCast($param);
        $this->assertSame($expected, $result);
    }
    
    public static function providerCast(): array
    {
        return [
            'request' => [
                'param' => new RouteParam('name', PropType::DI, [], true, JsonRpcRequest::class),
                'request' => JsonRpcRequest::fake([]),
                'expected' => JsonRpcRequest::class,
            ],
            'from di' => [
                'param' => new RouteParam('name', PropType::DI, [], true, DIObject::class),
                'request' => JsonRpcRequest::fake([]),
                'expected' => DIObject::class,
            ]
        ];
    }
    
    /**
     * @throws BindingResolutionException
     */
    #[DataProvider('providerCast')]
    public function testCast(RouteParam $param, JsonRpcRequest $request, string $expected)
    {
        $result = DIResolver::cast($param, $request);
        $this->assertInstanceOf($expected, $result);
    }
}
