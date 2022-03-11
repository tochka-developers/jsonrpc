<?php

namespace Tochka\JsonRpc\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\HttpRequestMiddleware;
use Tochka\JsonRpc\Support\ResponseCollection;

class TokenAuthMiddleware implements HttpRequestMiddleware
{
    public const ATTRIBUTE_AUTH = 'jsonrpc_auth';
    
    private string $headerName;
    private array $tokens;
    
    public function __construct(string $headerName = 'X-Access-Key', array $tokens = [])
    {
        $this->headerName = $headerName;
        $this->tokens = $tokens;
    }
    
    public function handleHttpRequest(ServerRequestInterface $request, callable $next): ResponseCollection
    {
        if (!$key = $request->getHeaderLine($this->headerName)) {
            return $next($request);
        }
        
        $service = array_search($key, $this->tokens, true);
        
        if ($service === false) {
            return $next($request);
        }
        
        $request = $request->withAttribute(self::ATTRIBUTE_AUTH, $service);
        
        return $next($request);
    }
}
