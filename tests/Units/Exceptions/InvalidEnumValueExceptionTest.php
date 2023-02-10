<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\InvalidEnumValueError;
use Tochka\JsonRpc\Exceptions\InvalidEnumValueException;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParametersError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\InvalidEnumValueException
 */
class InvalidEnumValueExceptionTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $expectedParameterName = 'testParameterName';
        $expectedActualValue = 'value';
        $expectedExpectedValues = ['bar', 'foo'];
        $expectedError = new InvalidEnumValueError($expectedParameterName, $expectedActualValue, $expectedExpectedValues);

        $exception = new InvalidEnumValueException($expectedParameterName, $expectedActualValue, $expectedExpectedValues);

        $expected = new JsonRpcError(
            AdditionalJsonRpcException::CODE_INVALID_PARAMETERS,
            AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS,
            new InvalidParametersError([$expectedError])
        );

        self::assertEquals($expected, $exception->getJsonRpcError());
    }
}
