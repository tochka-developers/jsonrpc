<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions\Errors;

use Tochka\JsonRpc\Exceptions\Errors\InvalidTypeError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\Errors\InvalidTypeError
 */
class InvalidTypeErrorTest extends DefaultTestCase
{
    public function testToArray(): void
    {
        $expectedParameterName = 'testParameterName';
        $expectedActualType = 'string';
        $expectedExpectedType = 'int';

        $error = new InvalidTypeError($expectedParameterName, $expectedActualType, $expectedExpectedType);

        $expected = [
            'object_name' => $expectedParameterName,
            'code' => InvalidParameterError::CODE_PARAMETER_INCORRECT_TYPE,
            'message' => InvalidParameterError::MESSAGE_PARAMETER_INCORRECT_TYPE,
            'meta' => [
                'actual_type' => $expectedActualType,
                'expected_type' => $expectedExpectedType
            ]
        ];

        self::assertEquals($expected, $error->toArray());
    }
}
