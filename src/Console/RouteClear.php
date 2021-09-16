<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Psr\SimpleCache\CacheInterface;

class RouteClear extends Command
{
    protected $signature = 'jsonrpc:route:clear';
    protected $description = 'Clear JsonRpc route cache';
    
    public function handle(): void
    {
        /** @var CacheInterface $cache */
        $cache = App::make('JsonRpcRouteCache');
        
        $cache->clear();
        $this->info('JsonRpc routes cache cleared!');
    }
}
