<?php

namespace Tochka\JsonRpc\Tests\TestControllers;

use Tochka\JsonRpc\Attributes\ApiParams;
use Tochka\JsonRpc\Tests\TestParams\ApiParamsObject;
use Tochka\JsonRpc\Tests\TestParams\DIObject;
use Tochka\JsonRpc\Tests\TestParams\TestAbstractClass;
use Tochka\JsonRpc\Tests\TestParams\TestEnumInt;
use Tochka\JsonRpc\Tests\TestParams\TestEnumPure;
use Tochka\JsonRpc\Tests\TestParams\TestEnumString;
use Tochka\JsonRpc\Tests\TestParams\TestPropInterface;

class ParserTestController
{
    public function noParams()
    {
    }

    public function noType($one)
    {
    }

    public function mixed(mixed $one)
    {
    }

    public function int(int $one)
    {
    }

    public function float(float $one)
    {
    }

    public function string(string $one)
    {
    }

    public function bool(bool $one)
    {
    }

    public function array(array $one)
    {
    }

    public function object(object $one)
    {
    }

    public function null(null $one)
    {
    }

    public function unionPrimitive(int|string|bool $one)
    {
    }

    public function enumString(TestEnumString $one)
    {
    }

    public function enumInt(TestEnumInt $one)
    {
    }

    public function apiParams(#[ApiParams] ApiParamsObject $one)
    {
    }

    // not allowed types

    public function apiParamsUnion(#[ApiParams] ApiParamsObject|DIObject $one)
    {
    }

    public function apiParamsIntersection(#[ApiParams] ApiParamsObject & TestPropInterface $one)
    {
    }

    public function apiParamsOptional(#[ApiParams] ApiParamsObject $one = new ApiParamsObject())
    {
    }

    public function apiParamsNullable(#[ApiParams] ?ApiParamsObject $one)
    {
    }

    public function apiParamsNotClass(#[ApiParams] int $one)
    {
    }

    public function apiParamsInterface(#[ApiParams] TestPropInterface $one)
    {
    }

    public function apiParamsAbstract(#[ApiParams] TestAbstractClass $one)
    {
    }

    public function enumPure(TestEnumPure $one)
    {
    }

    public function iterable(iterable $one)
    {
    }

    public function callable(callable $one)
    {
    }

    public function intersection(\Iterator&\Countable $one)
    {
    }
}
