<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tochka\JsonRpc\Facades\JsonRpcLog;

class JsonRpcHandler
{
    public function handle(\Exception $e)
    {
        $error = new \StdClass();

        if ($e instanceof HttpException) {
            /** @var HttpException $statusCode */
            $error->code = $e->getStatusCode();
            $error->message = !empty($e->getMessage()) ? $e->getMessage() : (Response::$statusTexts[$e->getStatusCode()] ?? 'Unknown error');
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

        JsonRpcLog::error(sprintf('JsonRpcException %d: %s', $error->code, $error->message), [Request::getContent()]);

        $handler = app(ExceptionHandler::class);
        $handler->report($e);

        return $error;
    }
}