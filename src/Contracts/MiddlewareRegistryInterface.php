<?php

namespace Tochka\JsonRpc\Contracts;

interface MiddlewareRegistryInterface
{
    public function setMiddleware(string $serverName, array $middleware, array $onceExecutedMiddleware): void;
    
    public function append(array $middleware, ?string $serverName = null): void;
    
    public function prepend(array $middleware, ?string $serverName = null): void;
    
    public function getMiddleware(string $serverName): array;
    
    public function getOnceExecutedMiddleware(string $serverName): array;
}
