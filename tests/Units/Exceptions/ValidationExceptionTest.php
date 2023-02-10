<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions;

use Illuminate\Support\Facades\Validator;
use Tochka\JsonRpc\Exceptions\ValidationException;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParametersError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\ValidationException
 */
class ValidationExceptionTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $expectedError1 = new InvalidParameterError(
            'requiredField',
            'RequiredWith',
            'The required field field is required when field is present.'
        );
        $expectedError2 = new InvalidParameterError('field', 'String', 'The field must be a string.');

        $validator = Validator::make(
            ['field' => false],
            ['requiredField' => 'required_with:field|string', 'field' => 'string']
        );
        $validator->passes();

        $exception = new ValidationException($validator);

        $expected = new JsonRpcError(
            AdditionalJsonRpcException::CODE_INVALID_PARAMETERS,
            AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS,
            new InvalidParametersError([$expectedError1, $expectedError2])
        );

        self::assertEquals($expected, $exception->getJsonRpcError());
    }
}
