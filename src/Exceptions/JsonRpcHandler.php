<?php

namespace Tochka\JsonRpc\Exceptions;

use Tochka\JsonRpc\Helpers\LogHelper;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonRpcHandler
{
    public function handle(\Exception $e)
    {
        $error = new \StdClass();

        if ($e instanceof HttpException) {
            /** @var HttpException $statusCode */
            $error->code = $e->getStatusCode();
            $error->message = !empty($e->getMessage()) ? $e->getMessage() : (!empty(Response::$statusTexts[$e->getStatusCode()]) ? Response::$statusTexts[$e->getStatusCode()] : 'Unknown error');
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

        LogHelper::log(LogHelper::TYPE_EXCEPTION, $error);

        $handler = app(ExceptionHandler::class);
        $handler->report($e);

        return $error;
    }
}