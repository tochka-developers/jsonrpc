<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\Hydrator\Contracts\ClassDefinitionsRegistryInterface;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-api
 */
class RouteCacheCommand extends Command
{
    protected $signature = 'jsonrpc:route:cache';
    protected $description = 'Cache JsonRpc routes';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(
        RouteCacheInterface $cache,
        ClassDefinitionsRegistryInterface $classDefinitionsRegistry,
        RouteAggregatorInterface $routeAggregator
    ): void {
        $cache->clear();
        $this->info('JsonRpc routes cache cleared!');

        $cache->setMultiple(
            [
                'routes' => $routeAggregator->getRoutes(),
                'classes' => $classDefinitionsRegistry->getAll()
            ]
        );
        $this->info('JsonRpc routes cached successfully!');
    }
}
