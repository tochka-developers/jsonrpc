<?php

namespace Tochka\JsonRpc\DTO;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;

class JsonRpcServerRequest
{
    private JsonRpcRequest $jsonRpcRequest;
    private ServerRequestInterface $serverRequest;
    private ?JsonRpcRoute $route = null;

    public function __construct(ServerRequestInterface $serverRequest, JsonRpcRequest $jsonRpcRequest)
    {
        $this->serverRequest = $serverRequest;
        $this->jsonRpcRequest = $jsonRpcRequest;
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
