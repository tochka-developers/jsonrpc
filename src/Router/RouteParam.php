<?php

namespace Tochka\JsonRpc\Router;

class RouteParam
{
    public function __construct(
        public string $name,
        public PropType $propType,
        public array $allowedTypes,
        public bool $isNullable,
        public string|null $className = null,
        public bool $isOptional = false,
    ) {
        if ($this->isNullable && !empty($this->allowedTypes) && !\in_array('null', $this->allowedTypes)) {
            $this->allowedTypes[] = 'null';
        }
    }
    
    public static function __set_state(array $array): self
    {
        return new self(
            $array['name'],
            $array['propType'],
            $array['allowedTypes'],
            $array['isNullable'],
            $array['className'],
            $array['isOptional'],
        );
    }
}
