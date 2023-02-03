<?php

namespace Tochka\JsonRpc\Exceptions\Errors;

use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;

class NotNullableValueError extends InvalidParameterError
{
    public function __construct(string $parameterName)
    {
        parent::__construct(
            parameterName: $parameterName,
            code:          self::CODE_PARAMETER_NOT_NULLABLE,
        );
    }
}
