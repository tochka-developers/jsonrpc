<?php

namespace Tochka\JsonRpc;

use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;
use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;

class MiddlewareRegistry implements MiddlewareRegistryInterface
{
    private array $middleware = [];
    private array $onceExecutedMiddleware = [];
    
    public function setMiddleware(string $serverName, array $middleware, array $onceExecutedMiddleware): void
    {
        $this->middleware[$serverName] = $middleware;
        $this->onceExecutedMiddleware[$serverName] = $onceExecutedMiddleware;
    }
    
    public function append(array $middleware, ?string $serverName = null): void
    {
        if ($serverName !== null) {
            if ($this->isOnceExecuted($middleware)) {
                $this->onceExecutedMiddleware[$serverName][] = $middleware;
            } else {
                $this->middleware[$serverName][] = $middleware;
            }
        } else {
            foreach ($this->middleware as $clientName => $_) {
                $this->append($middleware, $clientName);
            }
        }
    }
    
    public function prepend(array $middleware, ?string $serverName = null): void
    {
        if ($serverName !== null) {
            if ($this->isOnceExecuted($middleware)) {
                array_unshift($this->onceExecutedMiddleware[$serverName], $middleware);
            } else {
                array_unshift($this->middleware[$serverName], $middleware);
            }
        } else {
            foreach ($this->middleware as $clientName => $_) {
                $this->prepend($middleware, $clientName);
            }
        }
    }
    
    public function getMiddleware(string $serverName): array
    {
        return $this->middleware[$serverName] ?? [];
    }
    
    public function getOnceExecutedMiddleware(string $serverName): array
    {
        return $this->onceExecutedMiddleware[$serverName] ?? [];
    }
    
    private function isOnceExecuted(array $middleware): bool
    {
        $implements = class_implements($middleware[0]);
        return $implements && \in_array(OnceExecutedMiddleware::class, $implements, true);
    }
}
