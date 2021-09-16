<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Contracts\Support\Arrayable;

class JsonRpcInvalidParameterError implements Arrayable
{
    public const PARAMETER_ERROR_REQUIRED = 'required';
    public const PARAMETER_ERROR_NOT_NULLABLE = 'not_nullable';
    public const PARAMETER_ERROR_TYPE = 'type';
    
    public const MESSAGES = [
        self::PARAMETER_ERROR_REQUIRED => 'The field is required, but not present',
        self::PARAMETER_ERROR_NOT_NULLABLE => 'The field is cannot be null',
        self::PARAMETER_ERROR_TYPE => 'Field type incorrect',
    ];
    
    public string $code;
    public string $message;
    public string $fieldName;
    
    public function __construct(string $code, string $fieldName, ?string $message = null)
    {
        $this->code = $code;
        $this->message = $message ?? self::MESSAGES[$code] ?? 'Unknown cast error';
        $this->fieldName = $fieldName;
    }
    
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'object_name' => $this->fieldName,
        ];
    }
}
