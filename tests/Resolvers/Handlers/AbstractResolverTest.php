<?php

namespace Tochka\JsonRpc\Tests\Resolvers\Handlers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Resolvers\Handlers\AbstractResolver;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\VoidValue;

#[CoversClass(AbstractResolver::class)]
class AbstractResolverTest extends TestCase
{
    public static function providerGetTypeNormalized(): array
    {
        return [
            'boolean' => ['input' => true, 'expected' => 'bool'],
            'integer' => ['input' => 1, 'expected' => 'int'],
            'double' => ['input' => 1.0, 'expected' => 'float'],
            'NULL' => ['input' => null, 'expected' => 'null'],
            'string' => ['input' => 'one', 'expected' => 'string'],
            'array' => ['input' => [], 'expected' => 'array'],
            // resource cant be pass from api
        
        ];
    }
    
    #[DataProvider('providerGetTypeNormalized')]
    public function testGetTypeNormalized(mixed $input, mixed $expected): void
    {
        $result = AbstractResolver::getTypeNormalized($input);
        $this->assertSame($expected, $result);
    }

    public static function providerGetValue(): array
    {
        $routeParam = new RouteParam('param', PropType::Mixed, [], false);
        return [
            'array exist' => ['param' => $routeParam, 'input' => ['param' => 'ok'], 'expected' => 'ok'],
            'array not exist' => [
                'param' => $routeParam,
                'input' => ['notParam' => 'ok'],
                'expected' => new VoidValue()
            ],
            'array null' => ['param' => $routeParam, 'input' => ['param' => null], 'expected' => null],
            'object exist' => ['param' => $routeParam, 'input' => (object)['param' => 'ok'], 'expected' => 'ok'],
            'object not exist' => [
                'param' => $routeParam,
                'input' => (object)['notParam' => 'ok'],
                'expected' => new VoidValue()
            ],
            'object null' => ['param' => $routeParam, 'input' => (object) ['param' => null], 'expected' => null],
        ];
    }
    
    #[DataProvider('providerGetValue')]
    public function testGetValue(RouteParam $param, object|array $input, mixed $expected): void
    {
        $result = AbstractResolver::getValue($param, $input);
        if ($expected instanceof VoidValue) {
            $this->assertInstanceOf(VoidValue::class, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
    
    public static function providerTypeCheck(): array
    {
        return [
            'not types to check' => [
                'param' => new RouteParam('param', PropType::Mixed, [], false),
                'value' => '123',
                'expectException' => false,
            ],
            'type is ok' => [
                'param' => new RouteParam('param', PropType::Mixed, ['string'], false),
                'value' => '123',
                'expectException' => false,
            ],
            'type not ok' => [
                'param' => new RouteParam('param', PropType::Mixed, ['int'], false),
                'value' => '123',
                'expectException' => true,
            ]
        ];
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    #[DataProvider('providerTypeCheck')]
    public function testTypeCheck(RouteParam $param, mixed $value, bool $expectException): void
    {
        if ($expectException) {
            $this->expectException(JsonRpcInvalidParameterException::class);
        }
        AbstractResolver::typePassOrThrow($param, $value);
        if (!$expectException) {
            $this->assertTrue(true);
        }
    }
    
    public static function providerIsNullAndAllowNull(): array
    {
        return [
            'null, isNullable' => [
                'param' => new RouteParam('param', PropType::Mixed, [], true),
                'value' => null,
                'expected' => true,
            ],
            'null, not nullable' => [
                'param' => new RouteParam('param', PropType::Mixed, [], false),
                'value' => null,
                'expected' => null, // exception
            ],
            'not null' => [
                'param' => new RouteParam('param', PropType::Mixed, [], false),
                'value' => '123',
                'expected' => false,
            ],
            
        ];
    }
    
    #[DataProvider('providerIsNullAndAllowNull')]
    public function testIsNullAndAllowNull(RouteParam $param, mixed $value, bool|null $expected)
    {
        if ($expected === null) {
            $this->expectException(JsonRpcInvalidParameterException::class);
        }
        $result = AbstractResolver::isNullAndAllowNull($param, $value);
        if ($expected !== null) {
            $this->assertSame($expected, $result);
        }
    }
    
    public static function providerIsVoidAndAllowVoid(): array
    {
        return [
            'void, isOptional' => [
                'param' => new RouteParam('param', PropType::Mixed, [], true, null, true),
                'value' => new VoidValue(),
                'expected' => true,
            ],
            'void, not isOptional' => [
                'param' => new RouteParam('param', PropType::Mixed, [], false, null, false),
                'value' => new VoidValue(),
                'expected' => null, // exception
            ],
            'not void' => [
                'param' => new RouteParam('param', PropType::Mixed, [], false, null, false),
                'value' => '123',
                'expected' => false,
            ],
        ];
    }
    
    #[DataProvider('providerIsVoidAndAllowVoid')]
    public function testisVoidAndAllowVoid(RouteParam $param, mixed $value, bool|null $expected): void
    {
        if ($expected === null) {
            $this->expectException(JsonRpcInvalidParameterException::class);
        }
        $result = AbstractResolver::isVoidAndAllowVoid($param, $value);
        if ($expected !== null) {
            $this->assertSame($expected, $result);
        }
    }
    
}
