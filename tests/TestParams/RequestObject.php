<?php

namespace Tochka\JsonRpc\Tests\TestParams;

class RequestObject
{
    public string $string;
    public string $otherString;
    public ?RequestChildObject $data = null;
}
