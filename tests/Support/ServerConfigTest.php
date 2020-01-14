<?php

namespace Tochka\JsonRpc\Tests\Support;

use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\JsonRpc\Tests\TestHelpers\BarMiddleware;
use Tochka\JsonRpc\Tests\TestHelpers\BarOnceMiddleware;
use Tochka\JsonRpc\Tests\TestHelpers\FooMiddleware;
use Tochka\JsonRpc\Tests\TestHelpers\FooOnceMiddleware;
use Tochka\JsonRpc\Tests\TestHelpers\ReflectionTrait;

class ServerConfigTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers \Tochka\JsonRpc\Support\ServerConfig::__construct
     */
    public function testConstructDescription(): void
    {
        $description = 'Test description';
        $namespace = 'Test\Namespace';

        $config = new ServerConfig([
            'description' => $description,
            'namespace'   => $namespace,
        ]);

        $this->assertEquals($description, $config->description);
        $this->assertEquals($namespace, $config->namespace);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\ServerConfig::__construct
     */
    public function testConstructDefault(): void
    {
        $config = new ServerConfig([]);

        $this->assertEquals('JsonRpc Server', $config->description);
        $this->assertEquals('App\Http\Controllers', $config->namespace);
        $this->assertEquals([], $config->middleware);
        $this->assertEquals([], $config->onceExecutedMiddleware);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\ServerConfig::parseMiddlewareConfiguration
     * @throws \ReflectionException
     */
    public function testParseMiddlewareConfiguration(): void
    {
        $config = new ServerConfig([]);
        $middleware = [
            FooMiddleware::class,
            FooOnceMiddleware::class => [
                'foo'   => 'bar',
                'hello' => 'world',
            ],
            BarMiddleware::class     => [
                'foo'   => 'bar',
                'hello' => 'world',
            ],
            BarOnceMiddleware::class,
        ];

        $result = $this->callMethod($config, 'parseMiddlewareConfiguration', [$middleware]);

        $middlewareConfigured = [
            [FooMiddleware::class, []],
            [
                FooOnceMiddleware::class,
                [
                    'foo'   => 'bar',
                    'hello' => 'world',
                ],
            ],
            [
                BarMiddleware::class,
                [
                    'foo'   => 'bar',
                    'hello' => 'world',
                ],
            ],
            [BarOnceMiddleware::class, []],
        ];

        $this->assertEquals($middlewareConfigured, $result);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\ServerConfig::sortMiddleware
     * @throws \ReflectionException
     */
    public function testSortMiddleware(): void
    {
        $config = new ServerConfig([]);
        $middleware = [
            [FooMiddleware::class, ['foo' => 'foo']],
            [FooOnceMiddleware::class, []],
            [BarMiddleware::class, []],
            [BarOnceMiddleware::class, ['bar' => 'bar']],
        ];

        $this->callMethod($config, 'sortMiddleware', [$middleware]);

        $middlewareOnce = [
            [FooOnceMiddleware::class, []],
            [BarOnceMiddleware::class, ['bar' => 'bar']],
        ];
        $middlewareMany = [
            [FooMiddleware::class, ['foo' => 'foo']],
            [BarMiddleware::class, []],
        ];

        $this->assertEquals($middlewareOnce, $config->onceExecutedMiddleware);
        $this->assertEquals($middlewareMany, $config->middleware);
    }
}
