<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Support\Facades\Request;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\HttpRequestMiddleware;
use Tochka\JsonRpc\Contracts\JsonRpcRequestMiddleware;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\JsonRpcResponse;
use Tochka\JsonRpc\Support\ResponseCollection;

class ServiceValidationMiddleware implements HttpRequestMiddleware
{
    /** @var array|string */
    private $servers;
    
    public function __construct($servers = [])
    {
        $this->servers = $servers;
    }
    
    /**
     * @throws JsonRpcException
     */
    public function handleHttpRequest(ServerRequestInterface $request, callable $next): ResponseCollection
    {
        // если не заданы настройки - по умолчанию запрещаем доступ
        if (empty($this->servers)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        // если разрешено всем
        if ($this->servers === '*') {
            return $next($request);
        }

        if (!is_array($this->servers)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        // если разрешено всем
        if (in_array('*', $this->servers, true)) {
            return $next($request);
        }
    
       // $request->getServerParams()['']
        $ip = Request::ip();
        if (!in_array($ip, $this->servers, true)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
        }

        return $next($request);
    }
}
