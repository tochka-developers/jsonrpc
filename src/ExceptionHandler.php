<?php

namespace Tochka\JsonRpc;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler as GlobalExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
use Tochka\JsonRpc\Contracts\ExceptionHandlerInterface;
use Tochka\JsonRpc\Exceptions\HttpException;
use Tochka\JsonRpc\Standard\Contracts\JsonRpcExceptionInterface;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;

class ExceptionHandler implements ExceptionHandlerInterface
{
    private Container $container;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @throws \Throwable
     * @throws BindingResolutionException
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

        /** @var GlobalExceptionHandler $handler */
        $handler = $this->container->make(GlobalExceptionHandler::class);
        $handler->report($exception);

        return $error;
    }
}
