<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions\Errors;

use Tochka\JsonRpc\Exceptions\Errors\NotNullableValueError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\Errors\NotNullableValueError
 */
class NotNullableValueErrorTest extends DefaultTestCase
{
    public function testToArray(): void
    {
        $expectedParameterName = 'testParameterName';

        $error = new NotNullableValueError($expectedParameterName);

        $expected = [
            'object_name' => $expectedParameterName,
            'code' => InvalidParameterError::CODE_PARAMETER_NOT_NULLABLE,
            'message' => InvalidParameterError::MESSAGE_PARAMETER_NOT_NULLABLE,
        ];

        self::assertEquals($expected, $error->toArray());
    }
}
