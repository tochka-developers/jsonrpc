<?php

namespace Tochka\JsonRpc\Support;

class ServerConfig
{
    public const DYNAMIC_ENDPOINT_NONE = 'none';
    public const DYNAMIC_ENDPOINT_CONTROLLER_NAMESPACE = 'controller_namespace';
    public const DYNAMIC_ENDPOINT_FULL_CONTROLLER_NAME = 'controller_name';
    
    public string $summary;
    public string $description;
    public string $namespace;
    public string $controllerSuffix;
    public string $methodDelimiter;
    public string $endpoint;
    public string $dynamicEndpoint;
    public array $middleware = [];
    
    public function __construct(array $config)
    {
        $this->summary = data_get($config, 'summary', 'JsonRpc Server');
        $this->description = data_get($config, 'description', 'JsonRpc Server');
        $this->namespace = data_get($config, 'namespace', 'App\Http\Controllers');
        $this->controllerSuffix = data_get($config, 'controllerSuffix', 'Controller');
        $this->methodDelimiter = data_get($config, 'methodDelimiter', '_');
        $this->endpoint = data_get($config, 'endpoint', '/api/v1/public/jsonrpc');
        $this->dynamicEndpoint = data_get($config, 'dynamicEndpoint', self::DYNAMIC_ENDPOINT_NONE);
        $this->middleware = data_get($config, 'middleware', []);
    }
}
