<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Contracts\AuthInterface;
use Tochka\JsonRpc\Contracts\JsonRpcRequestMiddlewareInterface;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Standard\Exceptions\Additional\ForbiddenException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\UnauthorizedException;
use Tochka\JsonRpc\Standard\Exceptions\MethodNotFoundException;

/**
 * @psalm-api
 */
class AccessControlListMiddleware implements JsonRpcRequestMiddlewareInterface
{
    private array $acl;
    private AuthInterface $auth;

    public function __construct(AuthInterface $auth, array $acl = [])
    {
        $this->acl = $acl;
        $this->auth = $auth;
    }

    public function handleJsonRpcRequest(JsonRpcServerRequest $request, callable $next): ?JsonRpcResponse
    {
        $route = $request->getRoute();

        if ($route === null) {
            throw new MethodNotFoundException();
        }

        $service = $this->auth->getClient()->getName();

        $globalRules = (array)($this->acl['*'] ?? []);

        if ($route->controllerClass !== null) {
            $controllerRules = (array)($this->acl[$route->controllerClass] ?? []);
            if ($route->controllerMethod !== null) {
                $methodRules = (array)($this->acl[$route->controllerClass . '@' . $route->controllerMethod] ?? []);
            } else {
                $methodRules = [];
            }
        } else {
            $controllerRules = [];
            $methodRules = [];
        }


        // если не попали ни под одно правило - значит сервису нельзя
        if (empty($globalRules) && empty($controllerRules) && empty($methodRules)) {
            throw new ForbiddenException();
        }

        // если есть правила для метода - ориентируемся только на них
        if (!empty($methodRules)) {
            $this->checkRules($service, $methodRules);
        // иначе смотрим на правила для контроллера
        } elseif (!empty($controllerRules)) {
            $this->checkRules($service, $controllerRules);
        // ну и если даже их нет - смотрим глобальные правила
        } elseif (!empty($globalRules)) {
            $this->checkRules($service, $globalRules);
        }

        return $next($request);
    }

    protected function checkRules(string $service, array $rules): void
    {
        if (!in_array($service, $rules, true)) {
            if ($service === 'guest') {
                throw new UnauthorizedException();
            }
            if (!in_array('*', $rules, true)) {
                throw new ForbiddenException();
            }
        }
    }
}
