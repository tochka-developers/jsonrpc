<?php

namespace Tochka\JsonRpc\Router;

class Route
{
    /** @var array<string,RouteParam> */
    protected array $params = [];
    
    public function __construct(
        public readonly string $name,
        public readonly string $controllerClass,
        public readonly string $controllerMethod,
    ) {
    }
    
    public function addParam(RouteParam $param): void
    {
        $this->params[$param->name] = $param;
    }
    
    public function getParams(): array
    {
        return $this->params;
    }
    
    public static function __set_state(array $array): self
    {
        $instance = new self($array['name'], $array['controllerClass'], $array['controllerMethod']);
        foreach ($array['params'] as $param) {
            $instance->addParam($param);
        }
        
        return $instance;
    }
}
