<?php

namespace Tochka\JsonRpc\Tests\Router\Examples;

use Tochka\JsonRpc\Attributes\ApiDI;
use Tochka\JsonRpc\Attributes\ApiParams;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Tests\TestParams\MappedRequestObject;
use Tochka\JsonRpc\Tests\TestParams\RequestObject;

class ParamsTestController
{
    public function apiParams(#[ApiParams] RequestObject $one, #[ApiDI] JsonRpcRequest $request): void
    {
    }

    public function apiObject(RequestObject $one): void
    {
    }

    public function apiMappedParams(#[ApiParams] MappedRequestObject $one): void
    {
    }
}
