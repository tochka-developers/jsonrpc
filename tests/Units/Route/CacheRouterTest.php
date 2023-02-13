<?php

namespace Tochka\JsonRpc\Tests\Units\Route;

use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\Route\CacheRouter;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Route\CacheRouter
 */
class CacheRouterTest extends DefaultTestCase
{
    public function testAdd(): void
    {
        $expectedRoute = new JsonRpcRoute('testServer', 'testMethod');

        $cache = \Mockery::mock(RouteCacheInterface::class);
        $router = \Mockery::mock(RouterInterface::class);
        $router->shouldReceive('add')
            ->once()
            ->with($expectedRoute);

        $cacheRouter = new CacheRouter($router, $cache);

        $cacheRouter->add($expectedRoute);
    }

    public function testGetCacheHit(): void
    {
        $expectedServerName = 'testServer';
        $expectedMethod = 'testMethod';
        $expectedRoute = new JsonRpcRoute($expectedServerName, $expectedMethod);

        $cache = \Mockery::mock(RouteCacheInterface::class);
        $cache->shouldReceive('get')
            ->once()
            ->with('routes', [])
            ->andReturn([$expectedRoute->getRouteName() => $expectedRoute]);

        $router = \Mockery::mock(RouterInterface::class);
        $router->shouldReceive('get')
            ->never()
            ->with($expectedServerName, $expectedMethod, null, null);

        $cacheRouter = new CacheRouter($router, $cache);

        self::assertEquals($expectedRoute, $cacheRouter->get($expectedServerName, $expectedMethod));
    }

    public function testGetNoCache(): void
    {
        $expectedServerName = 'testServer';
        $expectedMethod = 'testMethod';
        $expectedRoute = new JsonRpcRoute($expectedServerName, $expectedMethod);

        $cache = \Mockery::mock(RouteCacheInterface::class);
        $cache->shouldReceive('get')
            ->once()
            ->with('routes', [])
            ->andReturn([]);

        $router = \Mockery::mock(RouterInterface::class);
        $router->shouldReceive('get')
            ->once()
            ->with($expectedServerName, $expectedMethod, null, null)
            ->andReturn($expectedRoute);

        $cacheRouter = new CacheRouter($router, $cache);

        self::assertEquals($expectedRoute, $cacheRouter->get($expectedServerName, $expectedMethod));
    }
}
