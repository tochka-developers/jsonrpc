<?php

namespace Tochka\JsonRpc\Exceptions\Errors;

use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;

class InvalidEnumValueError extends InvalidParameterError
{
    public function __construct(string $parameterName, mixed $actualValue, array $expectedValues)
    {
        parent::__construct(
            parameterName: $parameterName,
            code:          self::CODE_INCORRECT_VALUE,
            meta:          [
                               'actual_value' => $actualValue,
                               'expected_value' => $expectedValues
                           ]
        );
    }
}
