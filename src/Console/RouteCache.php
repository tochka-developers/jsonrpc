<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Facades\JsonRpcParamsResolver;
use Tochka\JsonRpc\Facades\JsonRpcRouteAggregator;

class RouteCache extends Command
{
    protected $signature = 'jsonrpc:route:cache';
    protected $description = 'Cache JsonRpc routes';
    
    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        /** @var CacheInterface $cache */
        $cache = App::make('JsonRpcRouteCache');
        
        $cache->clear();
        $this->info('JsonRpc routes cache cleared!');
        
        $routes = JsonRpcRouteAggregator::getRoutes();
        $classes = JsonRpcParamsResolver::getClasses();
        
        $cache->setMultiple([
            'routes' => $routes,
            'classes' => $classes,
        ]);
        $this->info('JsonRpc routes cached successfully!');
    }
}
