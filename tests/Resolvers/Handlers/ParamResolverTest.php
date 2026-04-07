<?php

namespace Tochka\JsonRpc\Tests\Resolvers\Handlers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Resolvers\Handlers\ParamsResolver;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;

class ParamResolverTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return ResolverTestHelper::canCastCases(PropType::RequestObject);
    }
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = ParamsResolver::canCast($param);
        $this->assertSame($expected, $result);
    }
}
