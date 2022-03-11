<?php

namespace Tochka\JsonRpc\Exceptions\RPC;

use Tochka\JsonRpc\Exceptions\JsonRpcError;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

class BusinessValidationErrorException extends JsonRpcException
{
    public function __construct(
        string $code,
        string $message,
        ?string $object_name = null,
        ?object $meta = null,
        ?\Throwable $previous = null
    ) {
        $error = new JsonRpcError($code, $message, $object_name, $meta);
        
        parent::__construct(self::CODE_VALIDATION_ERROR, null, [$error], $previous);
    }
}
