<?php

namespace Tochka\JsonRpc\Contracts;

interface GlobalCustomCasterInterface extends CustomCasterInterface
{
    public function canCast(string $expectedType): bool;
}
