<?php

namespace Tochka\JsonRpc;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Middleware\BaseMiddleware;
use Tochka\JsonRpc\Helpers\LogHelper;

class JsonRpcRequest
{
    public $call;

    public $id = null;
    public $controller;
    public $method;
    public $params = [];

    public $service = 'guest';

    public $options = [];

    public $hideDataLog;

    public function __construct(\StdClass $call, $options)
    {
        $this->call = $call;
        $this->options = $options;
        $this->id = !empty($call->id) ? $call->id : null;
    }

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

        LogHelper::log(LogHelper::TYPE_REQUEST, $this);

        $result = $this->controller->{$this->method}(...$this->params);

        LogHelper::log(LogHelper::TYPE_RESPONSE, $this);

        return $result;
    }
}