<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Support\Facades\Config;
use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;

class ServerConfig
{
    public readonly string $serverName;
    public readonly string $namespace;
    public readonly string $controllerSuffix;
    public readonly string $methodDelimiter;
    public array $middlewares = [];
    public array $onceExecutedMiddlewares = [];
    public readonly bool $allowParentMethods;
    
    public function __construct(string $serverName, array $config)
    {
        $this->serverName = $serverName;
        $this->namespace = data_get($config, 'namespace', 'App\Http\Controllers');
        $this->controllerSuffix = data_get($config, 'controllerSuffix', 'Controller');
        $this->methodDelimiter = data_get($config, 'methodDelimiter', '_');
        $middleware = $this->parseMiddlewareConfiguration($config['middleware'] ?? []);
        $this->sortMiddleware($middleware);
    }
    
    /**
     * Загружает конфигурацию из файла.
     * @param string $serverName
     * @param string $configName
     * @return self
     */
    public static function makeFromConfigFile(string $serverName, string $configName = 'jsonrpc'): self
    {
        $config = Config::get($configName . '.' . $serverName, []);
        
        return new self($serverName, $config);
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
                $this->onceExecutedMiddlewares[] = $m;
            } else {
                $this->middlewares[] = $m;
            }
        }
    }
}
