<?php

namespace Tochka\JsonRpc\Tests\Casters;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Casters\PrimitiveCaster;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

#[CoversClass(PrimitiveCaster::class)]
class PrimitiveCasterTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return CasterTestHelper::canCastCases(PropType::Primitive);
    }
    
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = PrimitiveCaster::canCast($param);
        $this->assertSame($expected, $result);
    }
    
    public static function providerCast(): array
    {
        return [
            ...CasterTestHelper::voidCases(PropType::Primitive),
            ...CasterTestHelper::nullCases(PropType::Primitive),
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
        $result = PrimitiveCaster::cast($param, $request);
        
        if ($expected === VoidValue::class) {
            $this->assertInstanceOf(VoidValue::class, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
}
