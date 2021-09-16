<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler as DefaultHandler;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionHandler
{
    /**
     * @param \Exception $e
     *
     * @return \StdClass
     * @throws BindingResolutionException
     * @throws \Throwable
     */
    public function handle(\Exception $e): \StdClass
    {
        $error = new \StdClass();

        if ($e instanceof HttpException) {
            /** @var HttpException $statusCode */
            $error->code = $e->getStatusCode();
            if (!empty($e->getMessage())) {
                $error->message = $e->getMessage();
            } else {
                $error->message = !empty(Response::$statusTexts[$e->getStatusCode()])
                    ? Response::$statusTexts[$e->getStatusCode()]
                    : 'Unknown error';
            }
        } elseif ($e instanceof JsonRpcException) {
            $error->code = $e->getCode();
            $error->message = $e->getMessage();
            if (null !== $e->getData()) {
                $error->data['errors'] = $e->getData();
            }
        } else {
            $error->code = $e->getCode();
            $error->message = $e->getMessage();
        }

        /** @var DefaultHandler $handler */
        $handler = Container::getInstance()->make(DefaultHandler::class);
        $handler->report($e);

        return $error;
    }
}
