<?php

namespace Tochka\JsonRpc\Support;

/**
 * @psalm-type ServerConfigArray = array{
 *   summary?: string,
 *   description?: string,
 *   namespace?: string,
 *   controllerSuffix?: string,
 *   methodDelimiter?: string,
 *   endpoint?: string,
 *   dynamicEndpoint?: string,
 *   middleware?: array<string, array>|array<string>
 * }
 */
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
    /** @var array<string, array>|array<string>|array */
    public array $middleware = [];

    /**
     * @param ServerConfigArray $config
     */
    public function __construct(array $config)
    {
        $this->summary = $config['summary'] ?? 'JsonRpc Server';
        $this->description = $config['description'] ?? 'JsonRpc Server';
        $this->namespace = $config['namespace'] ?? 'App\Http\Controllers';
        $this->controllerSuffix = $config['controllerSuffix'] ?? 'Controller';
        $this->methodDelimiter = $config['methodDelimiter'] ?? '_';
        $this->endpoint = $config['endpoint'] ?? '/api/v1/public/jsonrpc';
        $this->dynamicEndpoint = $config['dynamicEndpoint'] ?? self::DYNAMIC_ENDPOINT_NONE;
        $this->middleware = $config['middleware'] ?? [];
    }
}
