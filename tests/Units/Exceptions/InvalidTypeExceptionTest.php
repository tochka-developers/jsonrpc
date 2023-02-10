<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\InvalidTypeError;
use Tochka\JsonRpc\Exceptions\InvalidTypeException;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParametersError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\InvalidTypeException
 */
class InvalidTypeExceptionTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $expectedParameterName = 'testParameterName';
        $expectedActualType = 'string';
        $expectedExpectedType = 'int';
        $expectedError = new InvalidTypeError($expectedParameterName, $expectedActualType, $expectedExpectedType);

        $exception = new InvalidTypeException($expectedParameterName, $expectedActualType, $expectedExpectedType);

        $expected = new JsonRpcError(
            AdditionalJsonRpcException::CODE_INVALID_PARAMETERS,
            AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS,
            new InvalidParametersError([$expectedError])
        );

        self::assertEquals($expected, $exception->getJsonRpcError());
    }
}
