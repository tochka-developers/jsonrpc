<?php

namespace Tochka\JsonRpc\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;

/**
 * @psalm-api
 */
interface HttpRequestMiddlewareInterface extends MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param callable(ServerRequestInterface): JsonRpcResponseCollection $next
     * @return JsonRpcResponseCollection
     */
    public function handleHttpRequest(ServerRequestInterface $request, callable $next): JsonRpcResponseCollection;
}
