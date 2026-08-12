<?php

namespace Tochka\JsonRpc\Tests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tochka\JsonRpc\Attributes\ApiDI;
use Tochka\JsonRpc\Exceptions\JsonPrcRouterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Router\RouteParser;
use Tochka\JsonRpc\Tests\Router\Examples\ParserTestController;
use Tochka\JsonRpc\Tests\TestParams\ApiParamsObject;
use Tochka\JsonRpc\Tests\TestParams\NestedObject;
use Tochka\JsonRpc\Tests\TestParams\ObjectWithValidation;
use Tochka\JsonRpc\Tests\TestParams\TestEnumInt;
use Tochka\JsonRpc\Tests\TestParams\TestEnumString;

#[CoversClass(RouteParser::class)]
#[CoversClass(Route::class)]
#[CoversClass(RouteParam::class)]
class RouterParserTest extends TestCase
{
    /**
     * @throws \Exception
     */
    protected function makeRoute(string $className, string $method): Route
    {
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $parser = new RouteParser(new Route($method, $reflectionClass->getName(), $method));
        
        return $parser->fillRoute($reflectionMethod);
    }
    
    /**
     * @throws \Exception
     */
    public function testNoParam(): void
    {
        $route = $this->makeRoute(ParserTestController::class, 'noParams');
        $this->assertEmpty($route->getParams());
        $this->assertSame(ParserTestController::class, $route->controllerClass);
    }
    
    public static function providerOneParam(): array
    {
        return [
            'noType' => [
                'method' => 'noType',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Mixed,
                    allowedTypes: ['null'],
                    isNullable:   true,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'mixed' => [
                'method' => 'mixed',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Mixed,
                    allowedTypes: ['null'],
                    isNullable:   true,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive int' => [
                'method' => 'int',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['int'],
                    isNullable:   false,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive float' => [
                'method' => 'float',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['float'],
                    isNullable:   false,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive string' => [
                'method' => 'string',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['string'],
                    isNullable:   false,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive bool' => [
                'method' => 'bool',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['bool'],
                    isNullable:   false,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive array' => [
                'method' => 'array',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['array'],
                    isNullable:   false,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive object' => [
                'method' => 'object',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['object'],
                    isNullable:   false,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive null' => [
                'method' => 'null',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['null'],
                    isNullable:   true,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'primitive union' => [
                'method' => 'unionPrimitive',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Primitive,
                    allowedTypes: ['int', 'string', 'bool'],
                    isNullable:   false,
                    className:    null,
                    isOptional:   false,
                )
            ],
            'enum string' => [
                'method' => 'enumString',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Enum,
                    allowedTypes: ['string'],
                    isNullable:   false,
                    className:    TestEnumString::class,
                    isOptional:   false,
                )
            ],
            'enum int' => [
                'method' => 'enumInt',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Enum,
                    allowedTypes: ['int'],
                    isNullable:   false,
                    className:    TestEnumInt::class,
                    isOptional:   false,
                )
            ],
            'api params obj' => [
                'method' => 'apiParams',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::RequestObject,
                    allowedTypes: [],
                    isNullable:   false,
                    className:    ApiParamsObject::class,
                    isOptional:   false,
                )
            ],
            'api DI' => [
                'method' => 'apiDI',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::DI,
                    allowedTypes: [],
                    isNullable:   false,
                    className:    ApiDI::class,
                    isOptional:   false,
                )
            ],
            'api object' => [
                'method' => 'apiObject',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Object,
                    allowedTypes: ['object'],
                    isNullable:   false,
                    className:    ObjectWithValidation::class,
                    isOptional:   false,
                )
            ],
            'api nested object' => [
                'method' => 'apiNestedObject',
                'expected' => new RouteParam(
                    name:         'one',
                    propType:     PropType::Object,
                    allowedTypes: ['object'],
                    isNullable:   false,
                    className:    NestedObject::class,
                    isOptional:   false,
                )
            ],
        ];
    }
    // api params and di
    // api params with other params must be error
    // api params twice
    /**
     * @throws \Exception
     */
    #[DataProvider('providerOneParam')]
    public function testOneParam(string $method, RouteParam $expected): void
    {
        $route = $this->makeRoute(ParserTestController::class, $method);
        /** @var RouteParam $param */
        $param = array_first($route->getParams());
        $this->assertSame($expected->name, $param->name);
        $this->assertSame($expected->isNullable, $param->isNullable);
        $this->assertSame($expected->className, $param->className);
        $this->assertSame($expected->isOptional, $param->isOptional);
        $this->assertSame($expected->propType, $param->propType);
        $this->assertSame($expected->name, $param->name);
        $this->assertEqualsCanonicalizing($expected->allowedTypes, $param->allowedTypes);
    }
    
    
    public static function providerErrors(): array
    {
        return [
            'iterable' => ['method' => 'iterable'],
            'callable' => ['method' => 'callable'],
            'intersection' => ['method' => 'intersection'],
            'enumPure' => ['method' => 'enumPure'],
            'apiParams union' => ['method' => 'apiParamsUnion'],
            'apiParams intersection' => ['method' => 'apiParamsIntersection'],
            'apiParams optional' => ['method' => 'apiParamsOptional'],
            'apiParams nullable' => ['method' => 'apiParamsNullable'],
            'apiParams not class' => ['method' => 'apiParamsNotClass'],
            'apiParams not class, interface' => ['method' => 'apiParamsInterface'],
            'apiParams abstract' => ['method' => 'apiParamsAbstract'],
            'api DI union' => ['method' => 'apiDIUnion'],
            'api DI intersection' => ['method' => 'apiDiIntersection'],
            'api DI optional' => ['method' => 'apiDIOptional'],
            'api DI nullable' => ['method' => 'apiDINullable'],
            'api DI not class or interface' => ['method' => 'apiDINotClass'],
            'union type with class' => ['method' => 'unionWithClass'],
            'Intersection property' => ['method' => 'intersectionObjectProperty'],
        ];
    }
    
    /**
     * @throws \Exception
     */
    #[DataProvider('providerErrors')]
    public function testErrors(string $method)
    {
        $this->expectException(JsonPrcRouterException::class);
        $this->makeRoute(ParserTestController::class, $method);
    }
}
