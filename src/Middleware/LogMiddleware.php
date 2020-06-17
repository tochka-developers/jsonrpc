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
            'method'  => $request->call->method,
            'call'    => class_basename($request->controller) . '::' . $request->method,
            'id'      => $request->id,
            'service' => $request->service,
        ];

        $logRequest = ArrayHelper::fromObject($request->call);

        $globalRules = $hideParams['*'] ?? [];
        $controllerRules = $hideParams[get_class($request->controller)] ?? [];
        $methodRules = $hideParams[get_class($request->controller) . '@' . $request->method] ?? [];
        $rules = array_merge($globalRules, $controllerRules, $methodRules);
        $logRequest['params'] = LogHelper::hidePrivateData((array) ($request->call->params ?? []), $rules);

        Log::channel($channel)->info('New request', $logContext + ['request' => $logRequest]);

        $result = $next($request);

        if (isset($result->error)) {
            Log::channel($channel)
                ->info('Error', $logContext + [
                        'error' => $result->error,
                    ]);
        } else {
            Log::channel($channel)->info('Successful request', $logContext);
        }

        return $result;
    }
}
