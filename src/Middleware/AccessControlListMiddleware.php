<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class AccessControlListMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param JsonRpcRequest $request
     * @param callable $next
     * @param array $acl
     *
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle(JsonRpcRequest $request, callable $next, array $acl = [])
    {
        $route = $request->getRoute();
        
        if ($route === null) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }
        
        $service = $request->getAuthName();
        
        $globalRules = (array)($acl['*'] ?? []);
        $controllerRules = (array)($acl[$route->controllerClass] ?? []);
        $methodRules = (array)($acl[$route->controllerClass . '@' . $route->controllerMethod] ?? []);
        
        // если не попали ни под одно правило - значит сервису нельзя
        if (empty($globalRules) && empty($controllerRules) && empty($methodRules)) {
            throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
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
    
    /**
     * @param string $service
     * @param array $rules
     *
     * @throws JsonRpcException
     */
    protected function checkRules(string $service, array $rules): void
    {
        if (!in_array($service, $rules, true)) {
            if ($service === 'guest' || !in_array('*', $rules, true)) {
                throw new JsonRpcException(JsonRpcException::CODE_FORBIDDEN);
            }
        }
    }
}
