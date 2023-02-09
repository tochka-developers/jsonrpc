<?php

namespace Tochka\JsonRpc\Exceptions\Errors;

use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;

class InvalidTypeError extends InvalidParameterError
{
    public function __construct(string $parameterName, string $actualType, string $expectedType)
    {
        parent::__construct(
            parameterName: $parameterName,
            code:          self::CODE_PARAMETER_INCORRECT_TYPE,
            meta:          [
                               'actual_type' => $actualType,
                               'expected_type' => $expectedType
                           ]
        );
    }
}
