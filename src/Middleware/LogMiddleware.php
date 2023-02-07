<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Support\Facades\Log;
use Tochka\JsonRpc\Contracts\AuthInterface;
use Tochka\JsonRpc\Contracts\JsonRpcRequestMiddlewareInterface;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;

/**
 * @psalm-api
 */
class LogMiddleware implements JsonRpcRequestMiddlewareInterface
{
    private string $channel;
    /** @var array<string, array<string>> */
    private array $hideParams;
    private bool $logSuccessfulRequest;
    private AuthInterface $auth;

    /**
     * @param array<string, array<string>> $hideParams
     */
    public function __construct(
        AuthInterface $auth,
        string $channel = 'default',
        array $hideParams = [],
        bool $logSuccessfulRequest = true
    ) {
        $this->channel = $channel;
        $this->hideParams = $hideParams;
        $this->logSuccessfulRequest = $logSuccessfulRequest;
        $this->auth = $auth;
    }

    public function handleJsonRpcRequest(JsonRpcServerRequest $request, callable $next): JsonRpcResponse
    {
        $logContext = [];

        if ($request->getJsonRpcRequest()->id !== null) {
            $logContext['id'] = $request->getJsonRpcRequest()->id;
        }

        $route = $request->getRoute();
        $logRequest = $request->getJsonRpcRequest()->toArray();

        if ($route !== null) {
            $logContext['group'] = $route->group;
            $logContext['action'] = $route->action;
            $logContext['method'] = $route->jsonRpcMethodName;
            $logContext['call'] = ($route->controllerClass ?? '<NoController>') . '::' . ($route->controllerMethod ?? '<NoMethod>');
            $logContext['service'] = $this->auth->getClient()->getName();

            $globalRules = $this->hideParams['*'] ?? [];
            if ($route->controllerClass !== null) {
                $controllerRules = $this->hideParams[$route->controllerClass] ?? [];
                if ($route->controllerMethod !== null) {
                    $methodRules = $this->hideParams[$route->controllerClass . '@' . $route->controllerMethod] ?? [];
                } else {
                    $methodRules = [];
                }
            } else {
                $controllerRules = [];
                $methodRules = [];
            }

            $rules = array_merge($globalRules, $controllerRules, $methodRules);
            $logRequest['params'] = $this->hidePrivateData(
                $request->getJsonRpcRequest()->params ?? [],
                $rules
            );
        }

        Log::channel($this->channel)->info('New request', $logContext + ['request' => $logRequest]);

        try {
            $result = $next($request);

            if ($result->error !== null) {
                Log::channel($this->channel)->error(
                    'Error',
                    $logContext + [
                        'error' => $result->error,
                    ]
                );
            } elseif ($this->logSuccessfulRequest) {
                Log::channel($this->channel)->info('Successful request', $logContext);
            }

            return $result;
        } catch (JsonRpcException $e) {
            Log::channel($this->channel)->error(
                'Error',
                $logContext + [
                    'error' => $e->getJsonRpcError(),
                ]
            );

            throw $e;
        }
    }

    /**
     * @param array|object|null $data
     * @param array<string> $rules
     * @return array<string, mixed>
     */
    private function hidePrivateData(array|object|null $data, array $rules): array
    {
        if ($data === null) {
            return [];
        }

        if (is_object($data)) {
            $data = (array)$data;
        }

        /** @var array<string, mixed> $data */

        foreach ($rules as $rule) {
            $rule = explode('.', $rule);

            /** @var array<string, mixed> $data */
            $data = $this->hideDataByRule($data, $rule);
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param array<string> $rule
     * @return mixed
     */
    private function hideDataByRule(mixed $data, array $rule): mixed
    {
        if (is_object($data)) {
            $data = (array)$data;
        }

        if (!is_array($data)) {
            return $data;
        }

        /** @var array<string, mixed> $data */

        $field = array_shift($rule);

        /** @var array<string, mixed> $resultedData */
        $resultedData = [];

        if ($field === '*') {
            /** @psalm-suppress MixedAssignment */
            foreach ($data as $key => $value) {
                /** @psalm-suppress MixedAssignment */
                $resultedData[$key] = $this->hideDataByRule($value, $rule);
            }
        } elseif (isset($data[$field])) {
            if (count($rule)) {
                /** @psalm-suppress MixedAssignment */
                $resultedData[$field] = $this->hideDataByRule($data[$field], $rule);
            } else {
                $resultedData[$field] = '***';
            }
        }

        return $resultedData;
    }
}
