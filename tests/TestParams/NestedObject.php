<?php

namespace Tochka\JsonRpc\Tests\TestParams;

class NestedObject
{
    public int $int;
    public $noType;
    public string $string;
    public float $float;
    public TestEnumString $enumString;
    public TestEnumInt $enumInt;
    public ChildObject $object;
    public static string $staticProp;
}
