<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;

class ServerConfig
{
    public const DYNAMIC_ENDPOINT_NONE = 'none';
    public const DYNAMIC_ENDPOINT_CONTROLLER_NAMESPACE = 'controller_namespace';
    public const DYNAMIC_ENDPOINT_FULL_CONTROLLER_NAME = 'controller_name';
    
    public string $endpoint;
    public string $dynamicEndpoint;
    public string $summary;
    public string $description;
    public string $namespace;
    public string $controllerSuffix;
    public string $methodDelimiter;
    public array $middleware = [];
    public array $onceExecutedMiddleware = [];
    public bool $allowParentMethods;

    public function __construct(array $config)
    {
        $this->summary = data_get($config, 'summary', 'JsonRpc Server');
        $this->description = data_get($config, 'description', 'JsonRpc Server');
        $this->namespace = data_get($config, 'namespace', 'App\Http\Controllers');
        $this->controllerSuffix = data_get($config, 'controllerSuffix', 'Controller');
        $this->methodDelimiter = data_get($config, 'methodDelimiter', '_');
        $this->endpoint = data_get($config, 'endpoint', '/api/v1/public/jsonrpc');
        $this->dynamicEndpoint = data_get($config, 'dynamicEndpoint', self::DYNAMIC_ENDPOINT_NONE);
        $this->allowParentMethods = data_get($config, 'allowParentMethods', false);
        
        $middleware = $this->parseMiddlewareConfiguration($config['middleware'] ?? []);
        $this->sortMiddleware($middleware);
    }

    /**
     * @param $middleware
     *
     * @return array
     */
    protected function parseMiddlewareConfiguration($middleware): array
    {
        $result = [];
        foreach ($middleware as $name => $m) {
            if (is_array($m)) {
                $result[] = [$name, $m];
            } else {
                $result[] = [$m, []];
            }
        }

        return $result;
    }

    /**
     * @param array $middleware
     */
    protected function sortMiddleware(array $middleware): void
    {
        foreach ($middleware as $m) {
            $implements = class_implements($m[0]);
            if ($implements && in_array(OnceExecutedMiddleware::class, $implements, true)) {
                $this->onceExecutedMiddleware[] = $m;
            } else {
                $this->middleware[] = $m;
            }
        }
    }
}
