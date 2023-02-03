<?php

namespace Tochka\JsonRpc\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\InvalidEnumValueError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;

class InvalidEnumValueException extends InvalidParameterException
{
    public function __construct(
        string $parameterName,
        mixed $actualValue,
        array $expectedValues,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            new InvalidEnumValueError($parameterName, $actualValue, $expectedValues),
            $previous
        );
    }
}
