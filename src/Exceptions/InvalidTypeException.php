<?php

namespace Tochka\JsonRpc\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\InvalidTypeError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;

class InvalidTypeException extends InvalidParameterException
{
    public function __construct(
        string $parameterName,
        string $actualType,
        string $expectedType,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            new InvalidTypeError($parameterName, $actualType, $expectedType),
            $previous
        );
    }
}
