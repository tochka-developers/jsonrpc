<?php

namespace Tochka\JsonRpc\Tests\TestParams;

use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Traits\WithDataMap;

class MappedRequestObject
{
    use WithDataMap;

    public array|object $input;

    /**
     * Preserve the input supplied to a custom mapper.
     */
    public static function dataMap(RouteParam $param, array|object $data): self
    {
        $object = new self();
        $object->input = $data;

        return $object;
    }
}
