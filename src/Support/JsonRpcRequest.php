<?php

namespace Tochka\JsonRpc\Support;

class JsonRpcRequest
{
    public object $call;

    public ?string $id;
    public string $controller;
    public string $method;
    public array $params = [];

    public string $service = 'guest';

    public function __construct(object $call)
    {
        $this->call = $call;
        $this->id = !empty($call->id) ? $call->id : null;
    }
}
