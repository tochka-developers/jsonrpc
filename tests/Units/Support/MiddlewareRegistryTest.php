<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Illuminate\Container\Container;
use Tochka\JsonRpc\Contracts\HttpRequestMiddlewareInterface;
use Tochka\JsonRpc\Contracts\MiddlewareInterface;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\MiddlewareRegistry;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\JsonRpc\Tests\Stubs\FakeBarMiddleware;
use Tochka\JsonRpc\Tests\Stubs\FakeFooMiddleware;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\MiddlewareRegistry
 */
class MiddlewareRegistryTest extends DefaultTestCase
{
    private MiddlewareRegistry $registry;

    public function setUp(): void
    {
        parent::setUp();

        $this->registry = new MiddlewareRegistry(Container::getInstance());
    }

    public function testAppendMiddlewareAllServers(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'bar');

        $this->registry->appendMiddleware($appendMiddleware);

        $actualFoo = $this->registry->getMiddleware('foo');
        $actualBar = $this->registry->getMiddleware('bar');

        $expectedFoo = [$foo, $appendMiddleware];
        $expectedBar = [$bar, $appendMiddleware];

        self::assertEquals($expectedFoo, $actualFoo);
        self::assertEquals($expectedBar, $actualBar);
    }

    public function testAppendMiddlewareOneServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $this->registry->appendMiddleware($appendMiddleware);

        $actual = $this->registry->getMiddleware('foo');

        $expected = [$foo, $bar, $appendMiddleware];

        self::assertEquals($expected, $actual);
    }

    public function testPrependMiddlewareAllServers(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'bar');

        $this->registry->prependMiddleware($appendMiddleware);

        $actualFoo = $this->registry->getMiddleware('foo');
        $actualBar = $this->registry->getMiddleware('bar');

        $expectedFoo = [$appendMiddleware, $foo];
        $expectedBar = [$appendMiddleware, $bar];

        self::assertEquals($expectedFoo, $actualFoo);
        self::assertEquals($expectedBar, $actualBar);
    }

    public function testPrependMiddlewareOneServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $this->registry->prependMiddleware($appendMiddleware);

        $actual = $this->registry->getMiddleware('foo');

        $expected = [$appendMiddleware, $foo, $bar];

        self::assertEquals($expected, $actual);
    }

    public function testAddMiddlewareAfterExistingAllServers(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');
        $this->registry->appendMiddleware($bar, 'bar');
        $this->registry->appendMiddleware($foo, 'bar');

        $this->registry->addMiddlewareAfter($appendMiddleware, $foo::class);

        $fooResult = $this->registry->getMiddleware('foo');
        $barResult = $this->registry->getMiddleware('bar');

        $expectedFoo = [$foo, $appendMiddleware, $bar];
        $expectedBar = [$bar, $foo, $appendMiddleware];

        self::assertEquals($expectedFoo, $fooResult);
        self::assertEquals($expectedBar, $barResult);
    }

    public function testAddMiddlewareAfterNotExistingAllServers(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');
        $this->registry->appendMiddleware($bar, 'bar');
        $this->registry->appendMiddleware($foo, 'bar');

        $this->registry->addMiddlewareAfter($appendMiddleware, 'UnknownMiddleware');

        $fooResult = $this->registry->getMiddleware('foo');
        $barResult = $this->registry->getMiddleware('bar');

        $expectedFoo = [$foo, $bar, $appendMiddleware];
        $expectedBar = [$bar, $foo, $appendMiddleware];

        self::assertEquals($expectedFoo, $fooResult);
        self::assertEquals($expectedBar, $barResult);
    }

    public function testAddMiddlewareAfterExistingOneServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $this->registry->addMiddlewareAfter($appendMiddleware, $foo::class);

        $actual = $this->registry->getMiddleware('foo');

        $expected = [$foo, $appendMiddleware, $bar];

        self::assertEquals($expected, $actual);
    }

    public function testAddMiddlewareAfterNotExistingOneServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $this->registry->addMiddlewareAfter($appendMiddleware, 'UnknownMiddleware');

        $actual = $this->registry->getMiddleware('foo');

        $expected = [$foo, $bar, $appendMiddleware];

        self::assertEquals($expected, $actual);
    }

    public function testAddMiddlewareBeforeExistingAllServers(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');
        $this->registry->appendMiddleware($bar, 'bar');
        $this->registry->appendMiddleware($foo, 'bar');

        $this->registry->addMiddlewareBefore($appendMiddleware, $bar::class);

        $fooResult = $this->registry->getMiddleware('foo');
        $barResult = $this->registry->getMiddleware('bar');

        $expectedFoo = [$foo, $appendMiddleware, $bar];
        $expectedBar = [$appendMiddleware, $bar, $foo];

        self::assertEquals($expectedFoo, $fooResult);
        self::assertEquals($expectedBar, $barResult);
    }

    public function testAddMiddlewareBeforeNotExistingAllServers(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');
        $this->registry->appendMiddleware($bar, 'bar');
        $this->registry->appendMiddleware($foo, 'bar');

        $this->registry->addMiddlewareBefore($appendMiddleware, 'UnknownMiddleware');

        $fooResult = $this->registry->getMiddleware('foo');
        $barResult = $this->registry->getMiddleware('bar');

        $expectedFoo = [$appendMiddleware, $foo, $bar];
        $expectedBar = [$appendMiddleware, $bar, $foo];

        self::assertEquals($expectedFoo, $fooResult);
        self::assertEquals($expectedBar, $barResult);
    }

    public function testAddMiddlewareBeforeExistingOneServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $this->registry->addMiddlewareBefore($appendMiddleware, $bar::class);

        $actual = $this->registry->getMiddleware('foo');

        $expected = [$foo, $appendMiddleware, $bar];

        self::assertEquals($expected, $actual);
    }

    public function testAddMiddlewareBeforeNotExistingOneServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);
        $appendMiddleware = \Mockery::mock('AppendMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $this->registry->addMiddlewareBefore($appendMiddleware, 'UnknownMiddleware');

        $actual = $this->registry->getMiddleware('foo');

        $expected = [$appendMiddleware, $foo, $bar];

        self::assertEquals($expected, $actual);
    }

    public function testGetMiddlewareExistingServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $actual = $this->registry->getMiddleware('foo');

        $expected = [$foo, $bar];

        self::assertEquals($expected, $actual);
    }

    public function testGetMiddlewareWithInstanceOf(): void
    {
        $foo = \Mockery::mock('FooMiddleware', HttpRequestMiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $actual = $this->registry->getMiddleware('foo', HttpRequestMiddlewareInterface::class);

        $expected = [$foo];

        self::assertEquals($expected, $actual);
    }

    public function testGetMiddlewareNotExistingServer(): void
    {
        $foo = \Mockery::mock('FooMiddleware', MiddlewareInterface::class);
        $bar = \Mockery::mock('BarMiddleware', MiddlewareInterface::class);

        $this->registry->appendMiddleware($foo, 'foo');
        $this->registry->appendMiddleware($bar, 'foo');

        $actual = $this->registry->getMiddleware('bar');

        self::assertEquals([], $actual);
    }

    public function testAddMiddlewaresFromConfig(): void
    {
        $expectedFoo = 35;
        $expectedBar = 'test';

        $config = [
            FakeFooMiddleware::class => [
                'foo' => $expectedFoo,
                'bar' => $expectedBar
            ],
            FakeBarMiddleware::class,
        ];

        $serverConfig = new ServerConfig(['middleware' => $config]);

        $this->registry->addMiddlewaresFromConfig('foo', $serverConfig);

        /** @var array{FakeFooMiddleware, FakeBarMiddleware} $middlewares */
        $middlewares = $this->registry->getMiddleware('foo');

        self::assertCount(2, $middlewares);
        self::assertInstanceOf(FakeFooMiddleware::class, $middlewares[0]);
        self::assertInstanceOf(FakeBarMiddleware::class, $middlewares[1]);
        self::assertInstanceOf(Container::class, $middlewares[0]->container);
        self::assertEquals($expectedFoo, $middlewares[0]->foo);
        self::assertEquals($expectedBar, $middlewares[0]->bar);
    }

    public function testAddMiddlewaresFromConfigInvalidMiddleware(): void
    {
        $config = [
            \Mockery::mock()::class
        ];

        $serverConfig = new ServerConfig(['middleware' => $config]);

        self::expectException(InternalErrorException::class);
        self::expectExceptionMessage(JsonRpcException::MESSAGE_INTERNAL_ERROR);

        $this->registry->addMiddlewaresFromConfig('foo', $serverConfig);
    }
}
