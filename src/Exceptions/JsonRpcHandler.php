<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tochka\JsonRpc\JsonRpcRequest;

class JsonRpcHandler
{
    protected const EXCEPTION_MESSAGE = 'JsonRpc (method:"%s", id:"%s", service:"%s"): #%d %s';

    public function handle(\Exception $e)
    {
        $error = new \StdClass();

        if ($e instanceof HttpException) {
            /** @var HttpException $statusCode */
            $error->code = $e->getStatusCode();
            $error->message = !empty($e->getMessage()) ? $e->getMessage() :
                (!empty(Response::$statusTexts[$e->getStatusCode()]) ? Response::$statusTexts[$e->getStatusCode()] : 'Unknown error');
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

        /** @var JsonRpcRequest $request */
        $request = app(JsonRpcRequest::class);

        if (isset($request->call->method)) {
            $logContext = [
                'method' => $request->call->method,
                'call' => class_basename($request->controller) . '::' . $request->method,
                'id' => $request->id,
                'service' => $request->service,
            ];
        } else {
            $logContext = [];
        }

        Log::channel(config('jsonrpc.log.channel', 'default'))
            ->info('Error #' . $error->code . ': ' . $error->message, $logContext);

        $handler = app(ExceptionHandler::class);
        $handler->report($e);

        return $error;
    }
}