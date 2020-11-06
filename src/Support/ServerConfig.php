<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Contracts\OnceExecutedMiddleware;

class ServerConfig
{
    public $description;
    public $namespace;
    public $controllerSuffix;
    public $methodDelimiter;
    public $middleware = [];
    public $onceExecutedMiddleware = [];

    public function __construct(array $config)
    {
        $this->description = data_get($config, 'description', 'JsonRpc Server');
        $this->namespace = data_get($config, 'namespace', 'App\Http\Controllers');
        $this->controllerSuffix = data_get($config, 'controllerSuffix', 'Controller');
        $this->methodDelimiter = data_get($config, 'methodDelimiter', '_');

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
