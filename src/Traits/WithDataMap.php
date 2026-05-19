<?php

namespace Tochka\JsonRpc\Traits;

use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Resolvers\Handlers\AbstractResolver;
use Tochka\JsonRpc\Router\RouteParam;

trait WithDataMap
{
    /**
     * @throws JsonRpcInvalidParameterException
     * @throws \ReflectionException
     * @codeCoverageIgnore
     */
    public static function dataMap(RouteParam $param, array|object $data): self
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return AbstractResolver::createObjectWithoutConstructor($param, $data);
    }
}
