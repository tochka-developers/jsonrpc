<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\NotNullableValueError;
use Tochka\JsonRpc\Exceptions\NotNullableValueException;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParametersError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\NotNullableValueException
 */
class NotNullableValueExceptionTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $expectedParameterName = 'testParameterName';
        $expectedError = new NotNullableValueError($expectedParameterName);

        $exception = new NotNullableValueException($expectedParameterName);

        $expected = new JsonRpcError(
            AdditionalJsonRpcException::CODE_INVALID_PARAMETERS,
            AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS,
            new InvalidParametersError([$expectedError])
        );

        self::assertEquals($expected, $exception->getJsonRpcError());
    }
}
