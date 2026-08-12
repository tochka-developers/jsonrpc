<?php

namespace Tochka\JsonRpc\Tests\TestParams;

class NestedIntersectionObject
{
    public ChildObject & \Countable $object;
}
