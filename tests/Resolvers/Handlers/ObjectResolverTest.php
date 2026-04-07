<?php

namespace Tochka\JsonRpc\Tests\Resolvers\Handlers;

use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Resolvers\Handlers\ObjectResolver;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

#[CoversClass(ObjectResolver::class)]
class ObjectResolverTest extends TestCase
{
    public static function providerCanCast(): array
    {
        return ResolverTestHelper::canCastCases(PropType::Object);
    }
    
    #[DataProvider('providerCanCast')]
    public function testCanCast(RouteParam $param, $expected): void
    {
        $result = ObjectResolver::canCast($param);
        $this->assertSame($expected, $result);
    }
    
    public static function providerCast(): array
    {
        return [
            ...ResolverTestHelper::voidCases(PropType::Object),
            ...ResolverTestHelper::nullCases(PropType::Object),
            'bad type, only object allowed' => [
                'param' => new RouteParam('name', PropType::Object, ['object'], false),
                'request' => JsonRpcRequest::fake(['name' => 'string']),
                'expected' => JsonRpcInvalidParameterException::class,
            ],
            // todo
//            'object with validation, validation failed' => [
//                'param' => new RouteParam('name', PropType::Object, ['object'], false, ObjectWithValidation::class),
//                'request' => JsonRpcRequest::fake(['name' => (object) ['string' => 'hello']]),
//                'expected' => ValidationException::class,
//            ],
            
        ];
    }
    
    
    /**
     * @throws \ReflectionException
     * @throws ValidationException
     */
    #[DataProvider('providerCast')]
    public function testCast(RouteParam $param, JsonRpcRequest $request, mixed $expected): void
    {
        if ($expected === JsonRpcInvalidParameterException::class) {
            $this->expectException(JsonRpcInvalidParameterException::class);
        }
        if ($expected === ValidationException::class) {
            $this->expectException(ValidationException::class);
        }
        
        $result = ObjectResolver::cast($param, $request);
        
        if ($expected === VoidValue::class) {
            $this->assertInstanceOf(VoidValue::class, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
}
