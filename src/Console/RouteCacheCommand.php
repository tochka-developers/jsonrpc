<?php

namespace Tochka\JsonRpc\Console;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
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
        ParamsResolverInterface $paramsResolver,
        RouteAggregatorInterface $routeAggregator
    ): void {
        $cache->clear();
        $this->info('JsonRpc routes cache cleared!');

        $cache->setMultiple(
            [
                'routes' => $routeAggregator->getRoutes(),
                'classes' => $paramsResolver->getClasses(),
            ]
        );
        $this->info('JsonRpc routes cached successfully!');
    }
}
