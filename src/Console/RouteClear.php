<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Psr\SimpleCache\CacheInterface;
use Tochka\JsonRpc\Router\Router;
use Tochka\JsonRpc\Support\ServerConfig;

class RouteClear extends Command
{
    protected $signature = 'jsonrpc:route:clear {server?}';
    protected $description = 'Clear JsonRpc route cache';
    
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
    
    protected function handleOne(ServerConfig $config): void
    {
        $router = new Router($config);
        $router->clearRoutesCache();
        $this->info('ServerName:' . $config->serverName. ' cache cleared');
    }
}
