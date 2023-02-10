<?php

namespace Tochka\JsonRpc\Tests\Units\Console;

use Tochka\JsonRpc\Console\RouteCacheCommand;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Console\RouteCacheCommand
 */
class RouteCacheCommandTest extends DefaultTestCase
{
    public function testHandle(): void
    {
        $expectedRoutes = [
            new JsonRpcRoute('foo', 'foo'),
            new JsonRpcRoute('bar', 'bar'),
        ];

        $expectedClasses = [
            new ParameterObject('fooClass'),
            new ParameterObject('barClass'),
        ];

        $expectedValues = [
            'routes' => $expectedRoutes,
            'classes' => $expectedClasses
        ];

        $cache = \Mockery::mock(RouteCacheInterface::class);
        $cache->shouldReceive('clear')
            ->once()
            ->with();
        $cache->shouldReceive('setMultiple')
            ->once()
            ->with($expectedValues);

        $paramsResolvers = \Mockery::mock(ParamsResolverInterface::class);
        $paramsResolvers->shouldReceive('getClasses')
            ->once()
            ->with()
            ->andReturn($expectedClasses);

        $routeAggregator = \Mockery::mock(RouteAggregatorInterface::class);
        $routeAggregator->shouldReceive('getRoutes')
            ->once()
            ->with()
            ->andReturn($expectedRoutes);

        $command = \Mockery::mock(RouteCacheCommand::class)->makePartial();
        $command->shouldReceive('info');

        $command->handle($cache, $paramsResolvers, $routeAggregator);
    }
}
