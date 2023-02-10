<?php

namespace Tochka\JsonRpc\Exceptions\Errors;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InternalError;

class HttpExceptionError extends InternalError
{
    private HttpException $exception;

    public function __construct(HttpException $exception)
    {
        $this->exception = $exception;

        parent::__construct($exception);
    }

    public function toArray(): array
    {
        if (!empty($this->exception->getMessage())) {
            $message = $this->exception->getMessage();
        } else {
            /** @var string $message */
            $message = array_key_exists($this->exception->getStatusCode(), Response::$statusTexts)
                ? Response::$statusTexts[$this->exception->getStatusCode()]
                : 'Unknown error';
        }

        return [
            'exception' => [
                'name' => $this->exception::class,
                'code' => $this->exception->getStatusCode(),
                'message' => $message,
            ]
        ];
    }
}
