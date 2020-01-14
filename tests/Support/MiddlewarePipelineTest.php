<?php

namespace Tochka\JsonRpc\Tests\Support;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Support\MiddlewarePipeline;
use Tochka\JsonRpc\Tests\TestHelpers\BarMiddleware;
use Tochka\JsonRpc\Tests\TestHelpers\BarOnceMiddleware;
use Tochka\JsonRpc\Tests\TestHelpers\FooMiddleware;
use Tochka\JsonRpc\Tests\TestHelpers\ReflectionTrait;
use Tochka\JsonRpc\Tests\TestHelpers\TestClass;

class MiddlewarePipelineTest extends TestCase
{
    use ReflectionTrait;

    public $pipeline;

    public function setUp(): void
    {
        parent::setUp();

        $this->pipeline = new MiddlewarePipeline(Container::getInstance());
    }

    /**
     * @covers \Tochka\JsonRpc\Support\MiddlewarePipeline::parseAssociatedParams
     * @throws \ReflectionException
     */
    public function testEmptyParams(): void
    {
        $middlewareConfig = [FooMiddleware::class, []];
        [$name, $args] = $this->callMethod($this->pipeline, 'parseAssociatedParams', [$middlewareConfig]);

        $this->assertEquals(FooMiddleware::class, $name);
        $this->assertIsArray($args);
        $this->assertEmpty($args);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\MiddlewarePipeline::parseAssociatedParams
     * @throws \ReflectionException
     */
    public function testExtraParams(): void
    {
        $middlewareConfig = [
            BarMiddleware::class,
            [
                'bar'   => 'bar',
                'foo'   => 'foo',
                'extra' => 'extra',
            ],
        ];

        [, $args] = $this->callMethod($this->pipeline, 'parseAssociatedParams', [$middlewareConfig]);

        $this->assertEquals(['foo', 'bar'], $args);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\MiddlewarePipeline::parseAssociatedParams
     * @throws \ReflectionException
     */
    public function testNotRequiredParams(): void
    {
        $middlewareConfig = [
            BarMiddleware::class,
            [
                'bar' => 'bar',
            ],
        ];

        $this->expectException(\RuntimeException::class);

        $this->callMethod($this->pipeline, 'parseAssociatedParams', [$middlewareConfig]);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\MiddlewarePipeline::parseAssociatedParams
     * @throws \ReflectionException
     */
    public function testNotDefaultParams(): void
    {
        $middlewareConfig = [
            BarMiddleware::class,
            [
                'foo' => 'foo',
            ],
        ];

        [, $args] = $this->callMethod($this->pipeline, 'parseAssociatedParams', [$middlewareConfig]);

        $this->assertEquals(['foo', 123], $args);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\MiddlewarePipeline::parseAssociatedParams
     * @throws \ReflectionException
     */
    public function testDIParams(): void
    {
        $class = new TestClass();
        Container::getInstance()->instance(TestClass::class, $class);

        $middlewareConfig = [
            BarOnceMiddleware::class,
            [
                'foo' => 'foo',
            ],
        ];

        [, [$foo, $actualClass]] = $this->callMethod($this->pipeline, 'parseAssociatedParams', [$middlewareConfig]);

        $this->assertEquals('foo', $foo);
        $this->assertSame($class, $actualClass);
    }
}
