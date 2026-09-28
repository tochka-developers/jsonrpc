<?php

namespace Tochka\JsonRpc\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER|\Attribute::TARGET_PROPERTY)]
readonly class ApiValidation
{
    /**
     * @param string|array<int, string> $rules
     */
    public function __construct(
        public array|string $rules = [],
    ) {
    }
}
