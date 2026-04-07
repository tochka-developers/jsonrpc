<?php

namespace Tochka\JsonRpc\Tests\Resolvers\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Resolvers\Handlers\PrimitiveResolver;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

#[CoversClass(PrimitiveResolver::class)]
class PrimitiveResolverTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return ResolverTestHelper::canCastCases(PropType::Primitive);
    }
    
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = PrimitiveResolver::canCast($param);
        $this->assertSame($expected, $result);
    }
    
    public static function providerCast(): array
    {
        return [
            ...ResolverTestHelper::voidCases(PropType::Primitive),
            ...ResolverTestHelper::nullCases(PropType::Primitive),
            'type not fit' => [
                'param' => new RouteParam('name', PropType::Primitive, ['string'], false),
                'request' => JsonRpcRequest::fake(['name' => 1]),
                'expected' => JsonRpcInvalidParameterException::class
            ],
            'all ok' => [
                'param' => new RouteParam('name', PropType::Primitive, ['int'], false),
                'request' => JsonRpcRequest::fake(['name' => 1]),
                'expected' => 1
            ],
        
        ];
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    #[DataProvider('providerCast')]
    public function testCast(RouteParam $param, JsonRpcRequest $request, mixed $expected): void
    {
        if ($expected === JsonRpcInvalidParameterException::class) {
            $this->expectException(JsonRpcInvalidParameterException::class);
        }
        $result = PrimitiveResolver::cast($param, $request);
        
        if ($expected === VoidValue::class) {
            $this->assertInstanceOf(VoidValue::class, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
}
