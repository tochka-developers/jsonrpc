<?php

namespace Tochka\JsonRpc;

use Illuminate\Support\Facades\Log;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\Middleware\BaseMiddleware;

class JsonRpcRequest
{
    protected const REQUEST_MESSAGE = 'JsonRpc (%s): New request (%s/%s)';
    protected const RESPONSE_MESSAGE = 'JsonRpc (%s): Successful request (%s/%s)';

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

        Log::channel(config('jsonrpc.logChannel', 'default'))
            ->info(sprintf(self::REQUEST_MESSAGE, $this->id, $this->controller, $this->method),
                ArrayHelper::fromObject($this->call));

        $result = $this->controller->{$this->method}(...$this->params);

        Log::channel(config('jsonrpc.logChannel', 'default'))
            ->info(sprintf(self::RESPONSE_MESSAGE, $this->id, $this->controller, $this->method));

        return $result;
    }
}