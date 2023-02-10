<?php

namespace Tochka\JsonRpc\Tests\Units\Console;

use Tochka\JsonRpc\Console\RouteListCommand;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Console\RouteListCommand
 */
class RouteListCommandTest extends DefaultTestCase
{
    public function testHandle(): void
    {
        $expectedRoute = new JsonRpcRoute('testServer', 'testMethod', 'testGroup', 'testAction');
        $expectedRoute->controllerClass = 'testController';
        $expectedRoute->controllerMethod = 'testMethod';

        $expectedHeaders = ['Server', 'Group:Action', 'JsonRpc Method', 'Controller@Method'];
        $expectedRow = [
            $expectedRoute->serverName,
            $expectedRoute->group . ':' . $expectedRoute->action,
            $expectedRoute->jsonRpcMethodName,
            $expectedRoute->controllerClass . '@' . $expectedRoute->controllerMethod
        ];

        $routeAggregator = \Mockery::mock(RouteAggregatorInterface::class);
        $routeAggregator->shouldReceive('getRoutes')
            ->once()
            ->with()
            ->andReturn([$expectedRoute]);

        $command = \Mockery::mock(RouteListCommand::class)->makePartial();
        $command->shouldReceive('table')
            ->once()
            ->with($expectedHeaders, [$expectedRow]);

        $command->handle($routeAggregator);
    }
}
