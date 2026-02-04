<?php

namespace Tochka\JsonRpc\Contracts;

interface ShouldMapped
{
    public static function dataMap(object $data): self;
}
