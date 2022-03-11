<?php

namespace Tochka\JsonRpc\Exceptions\RPC;

use Illuminate\Support\MessageBag;
use Tochka\JsonRpc\Exceptions\JsonRpcError;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Exceptions\JsonRpcValidationErrorException;

class InvalidParametersException extends JsonRpcValidationErrorException
{
//    public function __construct(MessageBag $messageBag, ?\Throwable $previous = null)
//    {
//        $errors = [];
//
//        foreach ($messageBag->toArray() as $code => $message) {
//            $errors[] = new JsonRpcError($code, reset($message));
//        }
//
//        parent::__construct(self::CODE_INVALID_PARAMETERS, null, $errors, $previous);
//    }
}
