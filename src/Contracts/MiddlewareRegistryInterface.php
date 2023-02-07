<?php

namespace Tochka\JsonRpc\Contracts;

/**
 * @psalm-api
 */
interface MiddlewareRegistryInterface
{
    /**
     * @template T of MiddlewareInterface
     * @param string $serverName
     * @param class-string<T>|null $implements
     * @return array<T>|array<MiddlewareInterface>
     */
    public function getMiddleware(string $serverName, ?string $implements = null): array;

    public function prependMiddleware(MiddlewareInterface $middleware, ?string $serverName = null): void;

    public function appendMiddleware(MiddlewareInterface $middleware, ?string $serverName = null): void;

    /**
     * @param MiddlewareInterface $middleware
     * @param class-string $afterMiddleware
     * @param string|null $serverName
     * @return void
     */
    public function addMiddlewareAfter(
        MiddlewareInterface $middleware,
        string $afterMiddleware,
        ?string $serverName = null
    ): void;

    /**
     * @param MiddlewareInterface $middleware
     * @param class-string $beforeMiddleware
     * @param string|null $serverName
     * @return void
     */
    public function addMiddlewareBefore(
        MiddlewareInterface $middleware,
        string $beforeMiddleware,
        ?string $serverName = null
    ): void;
}
