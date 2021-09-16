<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Route\JsonRpcRoute;

class JsonRpcRequest
{
    private object $rawRequest;
    
    private string $jsonrpc;
    private ?string $id;
    private string $method;
    /** @var object|array */
    private $params;
    
    private ?JsonRpcRoute $route = null;
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
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getJsonRpc(): string
    {
        return $this->jsonrpc;
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    /**
     * @return array|object
     */
    public function getParams()
    {
        return $this->params;
    }
    
    public function getRoute(): ?JsonRpcRoute
    {
        return $this->route;
    }
    
    public function setRoute(JsonRpcRoute $route): void
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
