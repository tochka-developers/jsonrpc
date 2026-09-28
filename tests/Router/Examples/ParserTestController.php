<?php

namespace Tochka\JsonRpc\Tests\Router\Examples;

use Tochka\JsonRpc\Attributes\ApiDI;
use Tochka\JsonRpc\Attributes\ApiParams;
use Tochka\JsonRpc\Tests\TestParams\ApiParamsObject;
use Tochka\JsonRpc\Tests\TestParams\DIObject;
use Tochka\JsonRpc\Tests\TestParams\NestedIntersectionObject;
use Tochka\JsonRpc\Tests\TestParams\NestedObject;
use Tochka\JsonRpc\Tests\TestParams\ObjectWithValidation;
use Tochka\JsonRpc\Tests\TestParams\TestAbstractClass;
use Tochka\JsonRpc\Tests\TestParams\TestEnumInt;
use Tochka\JsonRpc\Tests\TestParams\TestEnumPure;
use Tochka\JsonRpc\Tests\TestParams\TestEnumString;
use Tochka\JsonRpc\Tests\TestParams\TestPropInterface;

class ParserTestController
{
    public function noParams(): void
    {
    }

    public function noType($one): void
    {
    }

    public function mixed(mixed $one): void
    {
    }

    public function int(int $one): void
    {
    }

    public function float(float $one): void
    {
    }

    public function string(string $one): void
    {
    }

    public function bool(bool $one): void
    {
    }

    public function array(array $one): void
    {
    }

    public function object(object $one): void
    {
    }

    public function null(null $one): void
    {
    }

    public function unionPrimitive(int|string|bool $one): void
    {
    }

    public function enumString(TestEnumString $one): void
    {
    }

    public function enumInt(TestEnumInt $one): void
    {
    }

    public function apiParams(#[ApiParams] ApiParamsObject $one): void
    {
    }
    
    public function apiDI(#[ApiDI] ApiDI $one): void
    {
    }
    
    public function apiObject(ObjectWithValidation $one): void
    {
    }
    
    public function apiNestedObject(NestedObject $one): void
    {
    }

    // not allowed types

    public function apiParamsUnion(#[ApiParams] ApiParamsObject|DIObject $one): void
    {
    }

    public function apiParamsIntersection(#[ApiParams] ApiParamsObject & TestPropInterface $one): void
    {
    }

    public function apiParamsOptional(#[ApiParams] ApiParamsObject $one = new ApiParamsObject()): void
    {
    }

    public function apiParamsNullable(#[ApiParams] ?ApiParamsObject $one): void
    {
    }

    public function apiParamsNotClass(#[ApiParams] int $one): void
    {
    }

    public function apiParamsInterface(#[ApiParams] TestPropInterface $one): void
    {
    }

    public function apiParamsAbstract(#[ApiParams] TestAbstractClass $one): void
    {
    }
    
    public function apiDIUnion(#[ApiDI] ApiParamsObject|DIObject $one): void
    {
    }
    
    public function apiDiIntersection(#[ApiDI] ApiParamsObject & TestPropInterface $one): void
    {
    }
    
    public function apiDIOptional(#[ApiDI] ApiParamsObject $one = new ApiParamsObject()): void
    {
    }
    
    public function apiDINullable(#[ApiDI] ?ApiParamsObject $one): void
    {
    }
    
    public function apiDINotClass(#[ApiDI] int $one): void
    {
    }

    public function enumPure(TestEnumPure $one): void
    {
    }

    public function iterable(iterable $one): void
    {
    }

    public function callable(callable $one): void
    {
    }

    public function intersection(\Iterator&\Countable $one): void
    {
    }
    
    public function unionWithClass(ApiParamsObject|int $one): void
    {
    }
    
    public function intersectionObjectProperty(NestedIntersectionObject $one): void
    {
    }
}
