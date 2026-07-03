<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

class BasicOrTokenAuthMiddleware implements OnceExecutedMiddleware
{
    /**
     * @throws JsonRpcException
     */
    public function handle(
        array $requests,
        callable $next,
        Request $httpRequest,
        string $headerName = 'X-Access-Key',
        array $tokens = []
    ) {
        $service = $this->basic($httpRequest, $tokens);
        if (!$service) {
            $service = $this->token($httpRequest, $tokens, $headerName);
        }
        
        if (!$service) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }
        
        foreach ($requests as $request) {
            $request->setAuthName($service);
        }
        
        return $next($requests);
    }
    
    protected function basic(Request $httpRequest, array $tokens): null|string
    {
        $password = $tokens[$httpRequest->getUser()] ?? null;
        if (empty($password)) {
            return null;
        }
        
        if ($password !== $httpRequest->getPassword()) {
            return null;
        }
        
        return $httpRequest->getUser();
    }
    
    protected function token(Request $httpRequest, array $tokens, string $headerName): null|string
    {
        if (!$key = $httpRequest->header($headerName)) {
            return null;
        }
        
        $service = array_search($key, $tokens, true);
        if ($service === false) {
            return null;
        }
        
        return $service;
    }
}
