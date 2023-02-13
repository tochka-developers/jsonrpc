<?php

namespace Tochka\JsonRpc\Tests\Units\Route\Parameters;

use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Route\Parameters\Parameter
 */
class ParameterTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $expectedName = 'testName';
        $expectedType = ParameterTypeEnum::TYPE_OBJECT();

        $parameter = new Parameter($expectedName, $expectedType);

        self::assertEquals($expectedName, $parameter->name);
        self::assertEquals($expectedType, $parameter->type);
        self::assertEquals(null, $parameter->parametersInArray);
        self::assertEquals(false, $parameter->nullable);
        self::assertEquals(false, $parameter->required);
        self::assertEquals(null, $parameter->defaultValue);
        self::assertEquals(false, $parameter->hasDefaultValue);
        self::assertEquals(null, $parameter->className);
        self::assertEquals(false, $parameter->castFromDI);
        self::assertEquals(false, $parameter->castFullRequest);
        self::assertEquals([], $parameter->annotations);
        self::assertEquals(null, $parameter->description);
    }

    public function test__set_state(): void
    {
        $expectedName = 'testName';
        $expectedType = ParameterTypeEnum::TYPE_OBJECT();
        $expectedParametersInArray = new Parameter('name', ParameterTypeEnum::TYPE_BOOLEAN());
        $expectedNullable = true;
        $expectedRequired = true;
        $expectedDefaultValue = 'value';
        $expectedHasDefaultValue = true;
        $expectedClassName = 'testClassName';
        $expectedCastFromDI = true;
        $expectedCastFullRequest = true;
        $expectedAnnotations = ['annotation1', 'annotation2'];
        $expectedDescription = 'test description';

        $values = [
            'name' => $expectedName,
            'type' => $expectedType,
            'parametersInArray' => $expectedParametersInArray,
            'nullable' => $expectedNullable,
            'required' => $expectedRequired,
            'defaultValue' => $expectedDefaultValue,
            'hasDefaultValue' => $expectedHasDefaultValue,
            'className' => $expectedClassName,
            'castFromDI' => $expectedCastFromDI,
            'castFullRequest' => $expectedCastFullRequest,
            'annotations' => $expectedAnnotations,
            'description' => $expectedDescription,
        ];

        $parameter = Parameter::__set_state($values);

        self::assertEquals($expectedName, $parameter->name);
        self::assertEquals($expectedType, $parameter->type);
        self::assertEquals($expectedParametersInArray, $parameter->parametersInArray);
        self::assertEquals($expectedNullable, $parameter->nullable);
        self::assertEquals($expectedRequired, $parameter->required);
        self::assertEquals($expectedDefaultValue, $parameter->defaultValue);
        self::assertEquals($expectedHasDefaultValue, $parameter->hasDefaultValue);
        self::assertEquals($expectedClassName, $parameter->className);
        self::assertEquals($expectedCastFromDI, $parameter->castFromDI);
        self::assertEquals($expectedCastFullRequest, $parameter->castFullRequest);
        self::assertEquals($expectedAnnotations, $parameter->annotations);
        self::assertEquals($expectedDescription, $parameter->description);
    }
}
