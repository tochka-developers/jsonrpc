<?php

namespace Tochka\JsonRpc\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\NotNullableValueError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;

class NotNullableValueException extends InvalidParameterException
{
    public function __construct(string $parameterName, ?\Throwable $previous = null)
    {
        parent::__construct(
            new NotNullableValueError($parameterName),
            $previous
        );
    }
}
