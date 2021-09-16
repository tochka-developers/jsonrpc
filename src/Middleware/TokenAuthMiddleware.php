<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;
use Tochka\JsonRpc\Support\JsonRpcRequest;

/**
 * Авторизация сервиса по токену в заголовке
 * Параметры:
 * string headerName - имя заголовка, откуда вычитывать токен
 * array tokens - ассоциативный массив вида [имя_сервиса => токен]
 */
class TokenAuthMiddleware implements OnceExecutedMiddleware
{
    /**
     * @param JsonRpcRequest[] $requests
     * @param callable $next
     * @param Request $httpRequest
     * @param string $headerName
     * @param array $tokens
     *
     * @return mixed
     */
    public function handle(
        array $requests,
        callable $next,
        Request $httpRequest,
        string $headerName = 'X-Access-Key',
        array $tokens = []
    ) {
        if (!$key = $httpRequest->header($headerName)) {
            return $next($requests);
        }
        
        $service = array_search($key, $tokens, true);
        
        if ($service === false) {
            return $next($requests);
        }
        
        foreach ($requests as $request) {
            $request->setAuthName($service);
        }
        
        return $next($requests);
    }
}
