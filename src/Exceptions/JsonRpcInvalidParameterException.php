<?php

namespace Tochka\JsonRpc\Exceptions;

class JsonRpcInvalidParameterException extends JsonRpcInvalidParametersException
{
    public function __construct(string $message, string $fieldName)
    {
        parent::__construct([new JsonRpcInvalidParameterError($message, $fieldName)]);
    }
}
