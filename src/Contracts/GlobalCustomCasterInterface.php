<?php

namespace Tochka\JsonRpc\Contracts;

interface GlobalCustomCasterInterface extends CustomCasterInterface
{
    /**
     * @param class-string $expectedType
     * @return bool
     */
    public function canCast(string $expectedType): bool;
}
