<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\Router;
use Tochka\JsonRpc\Support\ServerConfig;

class RouteList extends Command
{
    protected $signature = 'jsonrpc:route:list {server?}';
    protected $description = 'Show list JsonRpc routes';
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        $serverName = $this->argument('server');
        
        $configs = Config::get('jsonrpc');
        
        if ($serverName) {
            $this->handleOne(new ServerConfig($serverName, $configs[$serverName] ?? []));
            return;
        }
        
        foreach ($configs as $name => $config) {
            $this->handleOne(new ServerConfig($name, $config));
        }
    }
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    protected function handleOne(ServerConfig $serverConfig): void
    {
        $router = new Router($serverConfig);
        $router->getAll();
        $routes = $router->getAll();
        $this->info('ServerName:' . $serverConfig->serverName);
        $this->table(
            ['Method', 'Controller', 'Call'],
            array_map(static fn(Route $route) => [
                $route->name,
                $route->controllerClass,
                $route->controllerMethod,
            ], $routes)
        );
    }
}
