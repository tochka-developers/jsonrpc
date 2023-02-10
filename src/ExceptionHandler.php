<?php

namespace Tochka\JsonRpc;

use Illuminate\Contracts\Debug\ExceptionHandler as LaravelExceptionHandlerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
use Tochka\JsonRpc\Contracts\ExceptionHandlerInterface;
use Tochka\JsonRpc\Exceptions\HttpException;
use Tochka\JsonRpc\Standard\Contracts\JsonRpcExceptionInterface;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;

class ExceptionHandler implements ExceptionHandlerInterface
{
    private LaravelExceptionHandlerInterface $exceptionHandler;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(LaravelExceptionHandlerInterface $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * @throws \Throwable
     */
    public function handle(\Throwable $exception): JsonRpcError
    {
        if ($exception instanceof SymfonyHttpException) {
            $error = (new HttpException($exception))->getJsonRpcError();
        } elseif ($exception instanceof JsonRpcExceptionInterface) {
            $error = $exception->getJsonRpcError();
        } else {
            $error = (InternalErrorException::from($exception))->getJsonRpcError();
        }

        $this->exceptionHandler->report($exception);

        return $error;
    }
}
