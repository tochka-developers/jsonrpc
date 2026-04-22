<?php

namespace Tochka\JsonRpc\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class ApiMethod
{
    public function __construct(
        public ?string $name = null,
    ) {
    }
}
