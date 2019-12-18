<?php

namespace Tochka\JsonRpc\Support;

class JsonRpcRequest
{
    public $call;

    public $id;
    public $controller;
    public $method;
    public $params = [];

    public $service = 'guest';

    public function __construct(\StdClass $call)
    {
        $this->call = $call;
        $this->id = !empty($call->id) ? $call->id : null;
    }
}
