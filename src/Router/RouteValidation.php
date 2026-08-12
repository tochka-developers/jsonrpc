<?php

namespace Tochka\JsonRpc\Router;

class RouteValidation
{
    public function __construct(
        public array $rules = [],
        public array $messages = [],
        public array $attributes = [],
    ) {
    }
    
}
