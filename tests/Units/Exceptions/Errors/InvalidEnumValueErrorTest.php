<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions\Errors;

use Tochka\JsonRpc\Exceptions\Errors\InvalidEnumValueError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\Errors\InvalidEnumValueError
 */
class InvalidEnumValueErrorTest extends DefaultTestCase
{
    public function testToArray(): void
    {
        $expectedParameterName = 'testParameterName';
        $expectedActualValue = 'value';
        $expectedExpectedValues = ['bar', 'foo'];

        $error = new InvalidEnumValueError($expectedParameterName, $expectedActualValue, $expectedExpectedValues);

        $expected = [
            'object_name' => $expectedParameterName,
            'code' => InvalidParameterError::CODE_INCORRECT_VALUE,
            'message' => InvalidParameterError::MESSAGE_INCORRECT_VALUE,
            'meta' => [
                'actual_value' => $expectedActualValue,
                'expected_value' => $expectedExpectedValues
            ]
        ];

        self::assertEquals($expected, $error->toArray());
    }
}
