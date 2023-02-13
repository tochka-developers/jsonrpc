<?php

namespace Tochka\JsonRpc\Tests\Units\Route\Parameters;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum
 */
class ParameterTypeEnumTest extends DefaultTestCase
{
    public function fromVarTypeProvider(): array
    {
        return [
            [ParameterTypeEnum::TYPE_STRING(), 'string'],
            [ParameterTypeEnum::TYPE_STRING(), 'str'],
            [ParameterTypeEnum::TYPE_FLOAT(), 'double'],
            [ParameterTypeEnum::TYPE_BOOLEAN(), 'boolean'],
            [ParameterTypeEnum::TYPE_BOOLEAN(), 'bool'],
            [ParameterTypeEnum::TYPE_INTEGER(), 'integer'],
            [ParameterTypeEnum::TYPE_INTEGER(), 'int'],
            [ParameterTypeEnum::TYPE_ARRAY(), 'array'],
            [ParameterTypeEnum::TYPE_OBJECT(), 'object'],
            [ParameterTypeEnum::TYPE_MIXED(), 'mixed'],
        ];
    }

    /**
     * @dataProvider fromVarTypeProvider
     */
    public function testFromVarType(ParameterTypeEnum $expected, string $value): void
    {
        self::assertEquals($expected, ParameterTypeEnum::fromVarType($value));
    }

    public function fromReflectionTypeProvider(): array
    {
        return [
            [ParameterTypeEnum::TYPE_OBJECT(), false, 'stdClass'],
            [ParameterTypeEnum::TYPE_STRING(), true, 'string'],
            [ParameterTypeEnum::TYPE_FLOAT(), true, 'float'],
            [ParameterTypeEnum::TYPE_BOOLEAN(), true, 'bool'],
            [ParameterTypeEnum::TYPE_INTEGER(), true, 'int'],
            [ParameterTypeEnum::TYPE_ARRAY(), true, 'array'],
            [ParameterTypeEnum::TYPE_OBJECT(), true, 'object'],
            [ParameterTypeEnum::TYPE_MIXED(), true, 'mixed'],
        ];
    }

    /**
     * @dataProvider fromReflectionTypeProvider
     */
    public function testFromReflectionType(ParameterTypeEnum $expected, bool $isBuiltin, string $name): void
    {
        $reflectionType = \Mockery::mock(\ReflectionNamedType::class);
        $reflectionType->shouldReceive('isBuiltin')
            ->with()
            ->andReturn($isBuiltin);

        $reflectionType->shouldReceive('getName')
            ->with()
            ->andReturn($name);

        self::assertEquals($expected, ParameterTypeEnum::fromReflectionType($reflectionType));
    }

    public function fromDocBlockTypeProvider(): array
    {
        return [
            [ParameterTypeEnum::TYPE_STRING(), new String_()],
            [ParameterTypeEnum::TYPE_FLOAT(), new Float_()],
            [ParameterTypeEnum::TYPE_BOOLEAN(), new Boolean()],
            [ParameterTypeEnum::TYPE_INTEGER(), new Integer()],
            [ParameterTypeEnum::TYPE_ARRAY(), new Array_()],
            [ParameterTypeEnum::TYPE_OBJECT(), new Object_()],
            [ParameterTypeEnum::TYPE_MIXED(), new Mixed_()],
        ];
    }

    /**
     * @dataProvider fromDocBlockTypeProvider
     */
    public function testFromDocBlockType(ParameterTypeEnum $expected, Type $value): void
    {
        self::assertEquals($expected, ParameterTypeEnum::fromDocBlockType($value));
    }

    public function toJsonTypeProvider(): array
    {
        return [
            ['string', ParameterTypeEnum::TYPE_STRING()],
            ['number', ParameterTypeEnum::TYPE_FLOAT()],
            ['boolean', ParameterTypeEnum::TYPE_BOOLEAN()],
            ['integer', ParameterTypeEnum::TYPE_INTEGER()],
            ['array', ParameterTypeEnum::TYPE_ARRAY()],
            ['object', ParameterTypeEnum::TYPE_OBJECT()],
            ['any', ParameterTypeEnum::TYPE_MIXED()],
        ];
    }

    /**
     * @dataProvider toJsonTypeProvider
     */
    public function testToJsonType(string $expected, ParameterTypeEnum $enum): void
    {
        self::assertEquals($expected, $enum->toJsonType());
    }
}
