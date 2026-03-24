<?php

namespace Tochka\JsonRpc\Contracts;

interface ShouldMapped
{
    public static function dataMap(object|array $data): self;
}
