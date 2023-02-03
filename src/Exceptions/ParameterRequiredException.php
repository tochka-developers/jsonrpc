<?php

namespace Tochka\JsonRpc\Exceptions;

use Tochka\JsonRpc\Exceptions\Errors\ParameterRequiredError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;

class ParameterRequiredException extends InvalidParameterException
{
    public function __construct(string $parameterName, ?\Throwable $previous = null)
    {
        parent::__construct(
            new ParameterRequiredError($parameterName),
            $previous
        );
    }
}
