<?php

namespace Tochka\JsonRpc\Route;

use Tochka\JsonRpc\Route\Parameters\Parameter;

class JsonRpcRoute
{
    public string $serverName;
    public ?string $group = null;
    public ?string $action = null;
    public string $jsonRpcMethodName;
    public string $controllerClass;
    public string $controllerMethod;
    /** @var array<string, Parameter> */
    public array $parameters = [];
    public Parameter $result;
    
    public function __construct(
        string $serverName,
        string $jsonRpcMethodName,
        string $group = null,
        string $action = null
    ) {
        $this->serverName = $serverName;
        $this->group = $group;
        $this->action = $action;
        $this->jsonRpcMethodName = $jsonRpcMethodName;
    }
    
    public function getRouteName(): string
    {
        return implode(
            '@',
            [
                $this->serverName,
                $this->group,
                $this->action,
                $this->jsonRpcMethodName
            ]
        );
    }
    
    public static function __set_state(array $array): self
    {
        $instance = new self($array['serverName'], $array['jsonRpcMethodName'], $array['group'], $array['action']);
        $instance->controllerClass = $array['controllerClass'];
        $instance->controllerMethod = $array['controllerMethod'];
        $instance->parameters = $array['parameters'] ?? [];
        $instance->result = $array['result'];
        
        return $instance;
    }
}
