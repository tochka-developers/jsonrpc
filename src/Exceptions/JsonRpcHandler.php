<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tochka\JsonRpc\JsonRpcRequest;

class JsonRpcHandler
{
    protected const EXCEPTION_MESSAGE = 'JsonRpc (method:"%s", id:"%s", service:"%s"): #%d %s';

    /**
     * @param \Exception $e
     *
     * @return \StdClass
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
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


        /** @var JsonRpcRequest $request */
        $request = Container::getInstance()->make(JsonRpcRequest::class);

        if (isset($request->call->method)) {
            $logContext = [
                'method'  => $request->call->method,
                'call'    => class_basename($request->controller) . '::' . $request->method,
                'id'      => $request->id,
                'service' => $request->service,
            ];
        } else {
            $logContext = [];
        }

        Log::channel(Config::get('jsonrpc.log.channel', 'default'))
            ->info('Error #' . $error->code . ': ' . $error->message, $logContext);

        /** @var ExceptionHandler $handler */
        $handler = Container::getInstance()->make(ExceptionHandler::class);
        $handler->report($e);

        return $error;
    }
}