<?php

namespace Tochka\JsonRpc\DTO;

use Tochka\Hydrator\Definitions\DTO\MethodDefinition;
use Tochka\JsonRpc\Route\RouteName;

/**
 * @psalm-api
 */
final class JsonRpcRoute
{
    use RouteName {
        getRouteName as getRouteNameBy;
    }

    public function __construct(
        public readonly string $serverName,
        public readonly string $jsonRpcMethodName,
        public readonly ?string $group,
        public readonly ?string $action,
        public readonly MethodDefinition $methodDefinition,
    ) {
    }

    public function getRouteName(): string
    {
        return $this->getRouteNameBy($this->serverName, $this->jsonRpcMethodName, $this->group, $this->action);
    }
}
