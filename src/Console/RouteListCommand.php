<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-api
 */
class RouteListCommand extends Command
{
    protected $signature = 'jsonrpc:route:list';
    protected $description = 'Show list JsonRpc routes';

    public function handle(RouteAggregatorInterface $routeAggregator): void
    {
        $routes = $routeAggregator->getRoutes();

        $this->table(
            ['Server', 'Group:Action', 'JsonRpc Method', 'Controller@Method'],
            array_map(static fn (JsonRpcRoute $route) => [
                $route->serverName,
                ($route->group ?? '@') . ':' . ($route->action ?? '@'),
                $route->jsonRpcMethodName,
                ($route->controllerClass ?? '<NoController>') . '@' . ($route->controllerMethod ?? '<NoMethod>')
            ], $routes)
        );
    }
}
