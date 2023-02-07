<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-api
 */
class RouteClearCommand extends Command
{
    protected $signature = 'jsonrpc:route:clear';
    protected $description = 'Clear JsonRpc route cache';

    public function handle(RouteCacheInterface $cache): void
    {
        $cache->clear();
        $this->info('JsonRpc routes cache cleared!');
    }
}
