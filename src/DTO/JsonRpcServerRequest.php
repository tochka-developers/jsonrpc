<?php

namespace Tochka\JsonRpc\DTO;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;

/**
 * @psalm-api
 */
class JsonRpcServerRequest
{
    private ?JsonRpcRoute $route = null;

    public function __construct(
        private readonly ServerRequestInterface $serverRequest,
        private readonly JsonRpcRequest $jsonRpcRequest,
    )
    {
    }

    public function getJsonRpcRequest(): JsonRpcRequest
    {
        return $this->jsonRpcRequest;
    }

    public function getRoute(): ?JsonRpcRoute
    {
        return $this->route;
    }

    public function setRoute(?JsonRpcRoute $route): void
    {
        $this->route = $route;
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }
}
