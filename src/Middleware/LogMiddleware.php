<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Support\Facades\Log;
use Tochka\JsonRpc\Contracts\JsonRpcRequestMiddleware;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\Helpers\LogHelper;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\JsonRpcResponse;

class LogMiddleware implements JsonRpcRequestMiddleware
{
    private string $channel;
    private array $hideParams;
    
    public function __construct(string $channel = 'default', array $hideParams = [])
    {
        $this->channel = $channel;
        $this->hideParams = $hideParams;
    }
    
    public function handleJsonRpcRequest(JsonRpcRequest $request, callable $next): JsonRpcResponse
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
            
            $globalRules = $this->hideParams['*'] ?? [];
            $controllerRules = $this->hideParams[$route->controllerClass] ?? [];
            $methodRules = $this->hideParams[$route->controllerClass . '@' . $route->controllerMethod] ?? [];
            $rules = array_merge($globalRules, $controllerRules, $methodRules);
            $logRequest['params'] = LogHelper::hidePrivateData(
                (array)($request->getRawRequest()->params ?? []),
                $rules
            );
        }
        
        Log::channel($this->channel)->info('New request', $logContext + ['request' => $logRequest]);
        
        try {
            $result = $next($request);
    
            if (isset($result->error)) {
                Log::channel($this->channel)->info(
                    'Error',
                    $logContext + [
                        'error' => $result->error,
                    ]
                );
            } else {
                Log::channel($this->channel)->info('Successful request', $logContext);
            }
    
            return $result;
        } catch (JsonRpcException $e) {
            Log::channel($this->channel)->info(
                'Error',
                $logContext + [
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                ]
            );
            
            throw $e;
        }
    }
}
