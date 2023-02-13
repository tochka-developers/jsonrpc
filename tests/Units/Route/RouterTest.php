<?php

namespace Tochka\JsonRpc\Tests\Units\Route;

use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\Route\Router;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Route\Router
 */
class RouterTest extends DefaultTestCase
{

    public function testAdd(): void
    {
        $routeAggregator = \Mockery::mock(RouteAggregatorInterface::class);
        $router = new Router($routeAggregator);

        $expectedServer = 'testServer';
        $expectedMethod = 'testMethod';
        $expectedRoute = new JsonRpcRoute($expectedServer, $expectedMethod);

        $router->add($expectedRoute);

        self::assertEquals($expectedRoute, $router->get($expectedServer, $expectedMethod));
    }

    public function testGetExists(): void
    {
        $expectedServer = 'testServer';
        $expectedMethod = 'testMethod';

        $expectedRoute = new JsonRpcRoute($expectedServer, $expectedMethod);
        $fooRoute = new JsonRpcRoute($expectedServer, 'fooMethod');

        $routes = [
            $fooRoute->getRouteName() => $fooRoute,
            $expectedRoute->getRouteName() => $expectedRoute,
        ];

        $routeAggregator = \Mockery::mock(RouteAggregatorInterface::class);
        $routeAggregator->shouldReceive('getRoutesForServer')
            ->once()
            ->with($expectedServer)
            ->andReturn($routes);

        $router = new Router($routeAggregator);

        self::assertEquals($expectedRoute, $router->get($expectedServer, $expectedMethod));
        // call `get` method twice for check correctly cache-hit
        self::assertEquals($expectedRoute, $router->get($expectedServer, $expectedMethod));
    }

    public function testGetNull(): void
    {
        $expectedServer = 'testServer';

        $barRoute = new JsonRpcRoute($expectedServer, 'barMethod');
        $fooRoute = new JsonRpcRoute($expectedServer, 'fooMethod');

        $routes = [
            $fooRoute->getRouteName() => $fooRoute,
            $barRoute->getRouteName() => $barRoute,
        ];

        $routeAggregator = \Mockery::mock(RouteAggregatorInterface::class);
        $routeAggregator->shouldReceive('getRoutesForServer')
            ->once()
            ->with($expectedServer)
            ->andReturn($routes);

        $router = new Router($routeAggregator);

        self::assertNull($router->get($expectedServer, 'testMethod'));
    }
}
