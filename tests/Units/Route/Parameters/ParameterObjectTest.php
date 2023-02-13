<?php

namespace Tochka\JsonRpc\Tests\Units\Route\Parameters;

use Tochka\JsonRpc\Route\Parameters\ParameterObject;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Route\Parameters\ParameterObject
 */
class ParameterObjectTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $parameterObject = new ParameterObject('test');

        self::assertEquals('test', $parameterObject->className);
        self::assertEquals(null, $parameterObject->properties);
        self::assertEquals(null, $parameterObject->customCastByCaster);
        self::assertEquals([], $parameterObject->annotations);
    }

    public function test__set_state(): void
    {
        $expectedClassName = 'testClass';
        $expectedProperties = ['testClass'];
        $expectedCustomCastByCaster = 'testCaster';
        $expectedAnnotations = ['testAnnotation1', 'testAnnotation2'];

        $values = [
            'className' => $expectedClassName,
            'properties' => $expectedProperties,
            'customCastByCaster' => $expectedCustomCastByCaster,
            'annotations' => $expectedAnnotations,
        ];

        $parameterObject = ParameterObject::__set_state($values);

        self::assertEquals($expectedClassName, $parameterObject->className);
        self::assertEquals($expectedProperties, $parameterObject->properties);
        self::assertEquals($expectedCustomCastByCaster, $parameterObject->customCastByCaster);
        self::assertEquals($expectedAnnotations, $parameterObject->annotations);
    }
}
