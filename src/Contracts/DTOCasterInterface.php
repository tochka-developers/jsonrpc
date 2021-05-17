<?php

namespace Tochka\JsonRpc\Contracts;

interface DTOCasterInterface
{
    public function cast(string $className, object $params, string $parentFieldName = '');
}
