<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Support\Facades\Request;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\HttpRequestMiddlewareInterface;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\Standard\Exceptions\Additional\ForbiddenException;

/**
 * @psalm-api
 */
class ServiceValidationMiddlewareInterface implements HttpRequestMiddlewareInterface
{
    private array|string $servers;

    public function __construct(array|string $servers = [])
    {
        $this->servers = $servers;
    }

    public function handleHttpRequest(ServerRequestInterface $request, callable $next): JsonRpcResponseCollection
    {
        // если не заданы настройки - по умолчанию запрещаем доступ
        if (empty($this->servers)) {
            throw new ForbiddenException();
        }

        // если разрешено всем
        if ($this->servers === '*') {
            return $next($request);
        }

        if (!is_array($this->servers)) {
            throw new ForbiddenException();
        }

        // если разрешено всем
        if (in_array('*', $this->servers, true)) {
            return $next($request);
        }

        // $request->getServerParams()['']
        $ip = Request::ip();
        if (!in_array($ip, $this->servers, true)) {
            throw new ForbiddenException();
        }

        return $next($request);
    }
}
