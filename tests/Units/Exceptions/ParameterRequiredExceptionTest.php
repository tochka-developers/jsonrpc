<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\ParameterRequiredError;
use Tochka\JsonRpc\Exceptions\ParameterRequiredException;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParametersError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\ParameterRequiredException
 */
class ParameterRequiredExceptionTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $expectedParameterName = 'testParameterName';
        $expectedError = new ParameterRequiredError($expectedParameterName);

        $exception = new ParameterRequiredException($expectedParameterName);

        $expected = new JsonRpcError(
            AdditionalJsonRpcException::CODE_INVALID_PARAMETERS,
            AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS,
            new InvalidParametersError([$expectedError])
        );

        self::assertEquals($expected, $exception->getJsonRpcError());
    }
}
