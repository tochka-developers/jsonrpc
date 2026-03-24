<?php

namespace Tochka\JsonRpc\Tests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\RouteParam;

#[CoversClass(Route::class)]
class RouteTest extends TestCase
{
    public function testRoute(): void
    {
        $route = new Route('name', 'class', 'method');
        $route->addParam(new RouteParam('name', PropType::Mixed, [], false));
        $route->addParam(new RouteParam('name2', PropType::Mixed, [], false));
        $params = $route->getParams();
        $this->assertArrayHasKey('name', $params);
        $this->assertArrayHasKey('name2', $params);
        $this->assertSame('name', $params['name']->name);
        $this->assertSame('name2', $params['name2']->name);
    }
}
