<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Contracts\Support\Arrayable;

class JsonRpcError implements Arrayable
{
    private string $code;
    private string $message;
    private ?string $object_name = null;
    private ?object $meta = null;
    
    public function __construct(string $code, string $message, ?string $object_name = null, ?object $meta = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->object_name = $object_name;
        $this->meta = $meta;
    }
    
    public function toArray(): array
    {
        $result = [
            'code' => $this->code,
            'message' => $this->message,
        ];
        
        if ($this->object_name !== null) {
            $result['object_name'] = $this->object_name;
        }
        
        if ($this->meta !== null) {
            $result['meta'] = $this->meta;
        }
        
        return $result;
    }
}
