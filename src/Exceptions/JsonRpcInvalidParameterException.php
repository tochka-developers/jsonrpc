<?php

namespace Tochka\JsonRpc\Exceptions;

class JsonRpcInvalidParameterException extends JsonRpcInvalidParametersException
{
    public function __construct(string $code, string $fieldName, ?string $message = null, ?\Throwable $previous = null)
    {
        $error = new JsonRpcInvalidParameterError($code, $fieldName, $message);
        parent::__construct([$error], $previous);
    }
}
