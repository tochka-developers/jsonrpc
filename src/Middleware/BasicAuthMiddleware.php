<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

class BasicAuthMiddleware implements OnceExecutedMiddleware
{
    /**
     * @throws JsonRpcException
     */
    public function handle(array $requests, callable $next, Request $httpRequest, array $tokens = [])
    {
        if(!$httpRequest->getUser() || !$httpRequest->getPassword()) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        $password = $tokens[$httpRequest->getUser()] ?? null;
        if (empty($password)) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        if ($password !== $httpRequest->getPassword()) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        foreach ($requests as $request) {
            $request->setAuthName($httpRequest->getUser());
        }

        return $next($requests);
    }
}
