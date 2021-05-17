<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\JsonRpcRequest;

/**
 * Авторизация сервиса по токену
 * Параметры:
 * string headerName - имя заголовка, откуда вычитывать токен
 * array tokens - ассоциативный массив вида [имя_сервиса => токен]
 */
class TokenAuthMiddleware implements OnceExecutedMiddleware
{
    /**
     * @param JsonRpcRequest[] $requests
     * @param callable         $next
     * @param Request          $httpRequest
     * @param string           $headerName
     * @param array            $tokens
     *
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle(
        array $requests,
        callable $next,
        Request $httpRequest,
        string $headerName = 'X-Access-Key',
        array $tokens = []
    ) {
        if (!$key = $httpRequest->header($headerName)) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        $service = array_search($key, $tokens, true);

        if ($service === false) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        foreach ($requests as $request) {
            $request->service = $service;
        }

        return $next($requests);
    }
}
