<?php

namespace Tochka\JsonRpc\Router;

class Route
{
    /** @var array<string,RouteParam> */
    protected array $params = [];
    protected array $validation = [];
    
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
    
    public function addValidation($fieldName, array $validation): void
    {
        if (count($validation) === 0) {
            return;
        }
        
        $this->validation[$fieldName] = $validation;
    }
    
    public function getValidation(): array
    {
        return $this->validation;
    }
}
