<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Support\Facades\Log;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\Helpers\LogHelper;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class LogMiddleware
{
    public function handle(JsonRpcRequest $request, $next, string $channel = 'default', array $hideParams = [])
    {
        $logContext = [
            'id' => $request->getId(),
        ];
        
        $route = $request->getRoute();
        $logRequest = ArrayHelper::fromObject($request->getRawRequest());
        
        if ($route !== null) {
            $logContext['group'] = $route->group;
            $logContext['action'] = $route->action;
            $logContext['method'] = $route->jsonRpcMethodName;
            $logContext['call'] = $route->controllerClass . '::' . $route->controllerMethod;
            $logContext['service'] = $request->getAuthName();
    
            $globalRules = $hideParams['*'] ?? [];
            $controllerRules = $hideParams[$route->controllerClass] ?? [];
            $methodRules = $hideParams[$route->controllerClass . '@' . $route->controllerMethod] ?? [];
            $rules = array_merge($globalRules, $controllerRules, $methodRules);
            $logRequest['params'] = LogHelper::hidePrivateData((array) ($request->getRawRequest()->params ?? []), $rules);
        }

        Log::channel($channel)->info('New request', $logContext + ['request' => $logRequest]);

        $result = $next($request);

        if (isset($result->error)) {
            Log::channel($channel)->info(
                'Error',
                $logContext + [
                        'error' => $result->error,
                    ]
            );
        } else {
            Log::channel($channel)->info('Successful request', $logContext);
        }

        return $result;
    }
}
