<?php

namespace Tochka\JsonRpc\Contracts;

interface ShouldValidate
{
    public static function dataValidate(object $data): void;
}
