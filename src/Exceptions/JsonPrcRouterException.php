<?php

namespace Tochka\JsonRpc\Exceptions;

class JsonPrcRouterException extends JsonRpcException
{
    public function __construct(string $message)
    {
        parent::__construct(JsonRpcException::CODE_INTERNAL_ERROR, $message);
    }
}
