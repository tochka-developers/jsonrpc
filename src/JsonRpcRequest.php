<?php

namespace Tochka\JsonRpc;

use Illuminate\Support\Facades\Log;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\Middleware\BaseMiddleware;

class JsonRpcRequest
{
    public $call;

    public $id;
    public $controller;
    public $method;
    public $params = [];

    public $service = 'guest';

    public $options = [];

    public function __construct(\StdClass $call, $options)
    {
        $this->call = $call;
        $this->options = $options;
        $this->id = !empty($call->id) ? $call->id : null;
    }

    /**
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle()
    {
        $middlewareList = $this->options['middleware'];

        foreach ($middlewareList as $className) {
            /** @var BaseMiddleware $middleware */
            $middleware = new $className();
            $middleware->handle($this);
        }

        if (empty($this->controller) || empty($this->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }

        $logContext = [
            'method' => $this->call->method,
            'call' => class_basename($this->controller) . '::' . $this->method,
            'id' => $this->id,
            'service' => $this->service,
        ];

        Log::channel(config('jsonrpc.log.channel', 'default'))
            ->info('New request', $logContext + ['request' => ArrayHelper::fromObject($this->call)]);

        $result = $this->controller->{$this->method}(...$this->params);

        Log::channel(config('jsonrpc.log.channel', 'default'))
            ->info('Successful request', $logContext);

        return $result;
    }
}