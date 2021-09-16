<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Facades\JsonRpcRouteAggregator;
use Tochka\JsonRpc\Route\JsonRpcRoute;

class RouteList extends Command
{
    protected $signature = 'jsonrpc:route:list';
    protected $description = 'Show list JsonRpc routes';
    
    public function handle(): void
    {
        $routes = JsonRpcRouteAggregator::getRoutes();
        
        $this->table(
            ['Server', 'Group:Action', 'JsonRpc Method', 'Controller@Method'],
            array_map(static fn(JsonRpcRoute $route) => [
                $route->serverName,
                ($route->group ?? '@') . ':' . ($route->action ?? '@'),
                $route->jsonRpcMethodName,
                $route->controllerClass . '@' . $route->controllerMethod
            ], $routes)
        );
        
        $this->info('JsonRpc routes cached successfully!');
    }
}
