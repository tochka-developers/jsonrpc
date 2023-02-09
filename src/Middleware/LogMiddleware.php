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
    private bool $logResponse;
    private AuthInterface $auth;
    private array $additionalContext = [];

    /**
     * @param array<string, array<string>> $hideParams
     */
    public function __construct(
        AuthInterface $auth,
        string $channel = 'default',
        array $hideParams = [],
        bool $logResponse = false
    ) {
        $this->channel = $channel;
        $this->hideParams = $hideParams;
        $this->auth = $auth;
        $this->logResponse = $logResponse;
    }

    public function appendAdditionalContext(array $context): void
    {
        $this->additionalContext = array_merge($this->additionalContext, $context);
    }

    public function handleJsonRpcRequest(JsonRpcServerRequest $request, callable $next): JsonRpcResponse
    {
        $logContext = $this->additionalContext;

        $logContext['service'] = $this->auth->getClient()->getName();

        $route = $request->getRoute();
        $logRequest = $request->getJsonRpcRequest()->toArray();

        if ($route !== null) {
            if ($route->group !== null) {
                $logContext['group'] = $route->group;
            }
            if ($route->action !== null) {
                $logContext['action'] = $route->action;
            }
            $logContext['method'] = $route->jsonRpcMethodName;
            $logContext['call'] = ($route->controllerClass ?? '<NoController>') . '::' . ($route->controllerMethod ?? '<NoMethod>');


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

        $logContext['request'] = $logRequest;

        Log::channel($this->channel)->info('New request', $logContext);

        try {
            $result = $next($request);

            if ($result->error !== null) {
                Log::channel($this->channel)
                    ->error('Error request', $logContext + ['error' => $result->error->toArray()]);
            } elseif ($this->logResponse) {
                Log::channel($this->channel)
                    ->info('Successful request', $logContext + ['result' => $result->result]);
            }

            return $result;
        } catch (JsonRpcException $e) {
            Log::channel($this->channel)
                ->error('Error request', $logContext + ['error' => $e->getJsonRpcError()->toArray()]);

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

        $resultedData = $data;

        //dump($data);

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
                $resultedData[$field] = '<hide>';
            }
        } else {
            return $data;
        }

        return $resultedData;
    }
}
