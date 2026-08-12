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
        /** @var RouteParam[] $params */
        public array $params = [],
    ) {
        if ($this->isNullable && !\in_array('null', $this->allowedTypes)) {
            $this->allowedTypes[] = 'null';
        }
    }
}
