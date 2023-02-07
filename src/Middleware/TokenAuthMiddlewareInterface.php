<?php

namespace Tochka\JsonRpc\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\HttpRequestMiddlewareInterface;
use Tochka\JsonRpc\Contracts\AuthInterface;
use Tochka\JsonRpc\DTO\JsonRpcClient;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;

/**
 * @psalm-api
 */
class TokenAuthMiddlewareInterface implements HttpRequestMiddlewareInterface
{
    private string $headerName;
    /** @var array<string, string>  */
    private array $tokens;
    private AuthInterface $auth;

    /**
     * @param array<string, string> $tokens
     */
    public function __construct(AuthInterface $auth, string $headerName = 'X-Access-Key', array $tokens = [])
    {
        $this->headerName = $headerName;
        $this->tokens = $tokens;
        $this->auth = $auth;
    }

    public function handleHttpRequest(ServerRequestInterface $request, callable $next): JsonRpcResponseCollection
    {
        if (!$key = $request->getHeaderLine($this->headerName)) {
            return $next($request);
        }

        $service = array_search($key, $this->tokens, true);

        if ($service === false) {
            return $next($request);
        }

        $this->auth->setClient(new JsonRpcClient($service));

        return $next($request);
    }
}
