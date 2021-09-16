<?php

namespace Tochka\JsonRpc\Exceptions;

class JsonRpcInvalidParameterException extends JsonRpcInvalidParametersException
{
    public function __construct(string $code, string $fieldName, ?string $message = null)
    {
        parent::__construct([new JsonRpcInvalidParameterError($code, $fieldName, $message)]);
    }
}
