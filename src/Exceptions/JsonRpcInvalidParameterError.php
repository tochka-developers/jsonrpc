<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Contracts\Support\Arrayable;

class JsonRpcInvalidParameterError implements Arrayable
{
    public string $message;
    public string $fieldName;

    public function __construct(string $message, string $fieldName)
    {
        $this->message = $message;
        $this->fieldName = $fieldName;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'object'  => $this->fieldName,
        ];
    }
}
