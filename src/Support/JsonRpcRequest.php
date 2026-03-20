<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Router\Route;

class JsonRpcRequest
{
    use RequestValidation;
    
    private object $rawRequest;
    
    private string $jsonrpc;
    public readonly string|int|null $id;
    public readonly string $method;
    public readonly object|array $params;
    
    private ?Route $route = null;
    private string $authName = 'guest';
    
    public function __construct(object $rawRequest)
    {
        $this->rawRequest = $rawRequest;
        
        $this->jsonrpc = $rawRequest->jsonrpc;
        $this->method = $rawRequest->method;
        $this->params = $rawRequest->params ?? (object)[];
        $this->id = $rawRequest->id ?? null;
    }
    
    public function getRawRequest(): object
    {
        return $this->rawRequest;
    }
    
    /** @deprecated */
    public function getId(): string|int|null
    {
        return $this->id;
    }
    
    /** @deprecated */
    public function getJsonRpc(): string
    {
        return $this->jsonrpc;
    }
    
    /** @deprecated */
    public function getMethod(): string
    {
        return $this->method;
    }
    
    /** @deprecated */
    public function getParams(): mixed
    {
        return $this->params;
    }
    
    public function getRoute(): ?Route
    {
        return $this->route;
    }
    
    public function setRoute(Route $route): void
    {
        $this->route = $route;
    }
    
    public function getAuthName(): string
    {
        return $this->authName;
    }
    
    public function setAuthName(string $authName): void
    {
        $this->authName = $authName;
    }
}
