<?php

namespace Tochka\JsonRpc\Router;

class Route
{
    /** @var array<string,RouteParam> */
    protected array $params = [];
    protected RouteValidation $validation;
    
    public function __construct(
        public readonly string $name,
        public readonly string $controllerClass,
        public readonly string $controllerMethod,
    ) {
        $this->validation = new RouteValidation();
    }
    
    public function addParam(RouteParam $param): void
    {
        $this->params[$param->name] = $param;
    }
    
    public function getParams(): array
    {
        return $this->params;
    }
    
    public function setValidation(RouteValidation $validation): void
    {
        $this->validation = $validation;
    }

    public function getValidation(): RouteValidation
    {
        return $this->validation;
    }
}
