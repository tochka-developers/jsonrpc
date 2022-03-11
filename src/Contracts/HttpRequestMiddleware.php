<?php

namespace Tochka\JsonRpc\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Support\ResponseCollection;
use Tochka\JsonRpcSupport\Contracts\HttpRequestMiddleware as BaseMiddleware;

interface HttpRequestMiddleware extends BaseMiddleware
{
    public function handleHttpRequest(ServerRequestInterface $request, callable $next): ResponseCollection;
}
