<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions\Errors;

use Tochka\JsonRpc\Exceptions\Errors\ParameterRequiredError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\Errors\ParameterRequiredError
 */
class ParameterRequiredErrorTest extends DefaultTestCase
{
    public function testToArray(): void
    {
        $expectedParameterName = 'testParameterName';

        $error = new ParameterRequiredError($expectedParameterName);

        $expected = [
            'object_name' => $expectedParameterName,
            'code' => InvalidParameterError::CODE_PARAMETER_REQUIRED,
            'message' => InvalidParameterError::MESSAGE_PARAMETER_REQUIRED,
        ];

        self::assertEquals($expected, $error->toArray());
    }
}
