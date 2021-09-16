<?php

namespace Tochka\JsonRpc\Exceptions;

class JsonRpcInvalidParameterTypeException extends JsonRpcInvalidParameterException
{
    public function __construct(string $fieldName, string $expectedType, string $actualType)
    {
        parent::__construct(
            JsonRpcInvalidParameterError::PARAMETER_ERROR_TYPE,
            $fieldName,
            sprintf('Field type incorrect. Expected type [%s], actual type [%s]', $expectedType, $actualType)
        );
    }
}
