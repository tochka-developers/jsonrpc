<?php

namespace Tochka\JsonRpc\Tests\Casters;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Casters\MixedCaster;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

#[CoversClass(MixedCaster::class)]
class MixedCasterTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return CasterTestHelper::canCastCases(PropType::Mixed);
    }
    
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = MixedCaster::canCast($param);
        $this->assertSame($expected, $result);
    }
    
    public static function providerCast(): array
    {
        return [
            ...CasterTestHelper::voidCases(PropType::Mixed),
            'all ok int' => [
                'param' => new RouteParam('name', PropType::Mixed, [], false),
                'request' => JsonRpcRequest::fake(['name' => 1]),
                'expected' => 1
            ],
            'all ok string' => [
                'param' => new RouteParam('name', PropType::Mixed, [], false),
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
        $result = MixedCaster::cast($param, $request);
        
        if ($expected === VoidValue::class) {
            $this->assertInstanceOf(VoidValue::class, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
}
