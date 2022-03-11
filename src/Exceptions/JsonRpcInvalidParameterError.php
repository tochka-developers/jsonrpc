<?php

namespace Tochka\JsonRpc\Exceptions;

class JsonRpcInvalidParameterError extends JsonRpcError
{
    public const PARAMETER_ERROR_REQUIRED = 'required';
    public const PARAMETER_ERROR_NOT_NULLABLE = 'not_nullable';
    public const PARAMETER_ERROR_TYPE = 'type';
    
    public const MESSAGES = [
        self::PARAMETER_ERROR_REQUIRED => 'The field is required, but not present',
        self::PARAMETER_ERROR_NOT_NULLABLE => 'The field is cannot be null',
        self::PARAMETER_ERROR_TYPE => 'Field type incorrect',
    ];
    
    public function __construct(string $code, string $fieldName, ?string $message = null, ?object $meta = null)
    {
        parent::__construct($code, $message ?? self::MESSAGES[$code] ?? 'Unknown cast error', $fieldName, $meta);
    }
}
