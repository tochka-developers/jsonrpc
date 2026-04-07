<?php

namespace Tochka\JsonRpc\Tests\Resolvers\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Resolvers\Handlers\EnumResolver;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;
use Tochka\JsonRpc\Tests\TestParams\TestEnumInt;
use Tochka\JsonRpc\Tests\TestParams\TestEnumString;

#[CoversClass(EnumResolver::class)]
class EnumResolverTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return ResolverTestHelper::canCastCases(PropType::Enum);
    }
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = EnumResolver::canCast($param);
        $this->assertSame($expected, $result);
    }
    
    public static function providerCast(): array
    {
        return [
            ...ResolverTestHelper::voidCases(PropType::Enum),
            ...ResolverTestHelper::nullCases(PropType::Enum),
            'type not fit, int => string' => [
                'param' => new RouteParam('name', PropType::Enum, ['string'], false, TestEnumString::class),
                'request' => JsonRpcRequest::fake(['name' => 1]),
                'expected' => JsonRpcInvalidParameterException::class
            ],
            'type not fit, string => int' => [
                'param' => new RouteParam('name', PropType::Enum, ['int'], false, TestEnumInt::class),
                'request' => JsonRpcRequest::fake(['name' => '123']),
                'expected' => JsonRpcInvalidParameterException::class
            ],
            'value enum mismatch' => [
                'param' => new RouteParam('name', PropType::Enum, ['string'], false, TestEnumInt::class),
                'request' => JsonRpcRequest::fake(['name' => '123']),
                'expected' => JsonRpcInvalidParameterException::class
            ],
            'ok for string' => [
                'param' => new RouteParam('name', PropType::Enum, ['string'], false, TestEnumString::class),
                'request' => JsonRpcRequest::fake(['name' => 'open']),
                'expected' => TestEnumString::Open,
            ],
            'ok for int' => [
                'param' => new RouteParam('name', PropType::Enum, ['int'], false, TestEnumInt::class),
                'request' => JsonRpcRequest::fake(['name' => 2]),
                'expected' => TestEnumInt::Two,
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
        $result = EnumResolver::cast($param, $request);
        
        if ($expected === VoidValue::class) {
            $this->assertInstanceOf(VoidValue::class, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
}
