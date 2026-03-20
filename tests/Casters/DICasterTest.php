<?php

namespace Tochka\JsonRpc\Tests\Casters;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Casters\DICaster;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Tests\TestParams\DIObject;

#[CoversClass(DICaster::class)]
class DICasterTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return CasterTestHelper::canCastCases(PropType::DI);
    }
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = DICaster::canCast($param);
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
        $result = DICaster::cast($param, $request);
        $this->assertInstanceOf($expected, $result);
    }
}
