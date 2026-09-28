<?php

namespace Tochka\JsonRpc\Tests\TestParams;

class ChildObject
{
    public int $int;
    public int|string $union;
    public string $string;
    public float $float;
    public TestEnumString $enumString;
    public TestEnumInt $enumInt;
}
