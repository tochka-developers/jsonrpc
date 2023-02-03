<?php

namespace Tochka\JsonRpc\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
use Tochka\JsonRpc\Exceptions\Errors\HttpExceptionError;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;

class HttpException extends InternalErrorException
{
    public function __construct(SymfonyHttpException $exception)
    {
        parent::__construct(null, new HttpExceptionError($exception), $exception);
    }
}
