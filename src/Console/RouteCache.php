<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Router\Router;
use Tochka\JsonRpc\Support\ServerConfig;

class RouteCache extends Command
{
    protected $signature = 'jsonrpc:route:cache {server?}';
    protected $description = 'Cache JsonRpc routes';
    
    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
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
    protected function handleOne(ServerConfig $config): void
    {
        $router = new Router($config);
        $router->clearRoutesCache();
        $this->info('ServerName:' . $config->serverName. ' cache cleared');
        $router->cacheRoutes();
        $this->info('ServerName:' . $config->serverName. ' cache created');
    }
}
