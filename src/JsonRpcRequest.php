<?php

namespace Tochka\JsonRpc;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Middleware\MethodClosureMiddleware;

class JsonRpcRequest
{
    public $call;

    public $id = null;
    public $controller;
    public $method;
    public $params = [];

    public $service = 'guest';

    public function __construct(\StdClass $call)
    {
        $this->call = $call;
        $this->id = !empty($call->id) ? $call->id : null;
    }

    public function handle()
    {
        $middlewareList = config('jsonrpc.middleware', [MethodClosureMiddleware::class]);

        foreach ($middlewareList as $className) {
            $middleware = new $className();
            $middleware->handle($this);
        }

        if (empty($this->controller) || empty($this->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }

        return $this->controller->{$this->method}(...$this->params);
    }
}