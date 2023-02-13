<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Mockery\Mock;
use Spiral\Attributes\ReaderInterface;
use Tochka\JsonRpc\Support\AnnotationReader;
use Tochka\JsonRpc\Tests\Stubs\FakeBenSampoEnum;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\AnnotationReader
 */
class AnnotationReaderTest extends DefaultTestCase
{
    private ReaderInterface|Mock $readerMock;
    private AnnotationReader $annotationReader;

    public function setUp(): void
    {
        parent::setUp();

        $this->readerMock = \Mockery::mock(ReaderInterface::class);
        $this->annotationReader = new AnnotationReader($this->readerMock);
    }


    public function testGetFunctionMetadata(): void
    {
        $function = new \ReflectionFunction(function() {});
        $name = 'test';
        $expected = [];

        $this->readerMock->shouldReceive('getFunctionMetadata')
            ->once()
            ->with($function, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->getFunctionMetadata($function, $name);

        self::assertEquals($expected, $actual);
    }

    public function testFirstFunctionMetadata(): void
    {
        $function = new \ReflectionFunction(function() {});
        $name = 'test';
        $expected = (object)[];

        $this->readerMock->shouldReceive('firstFunctionMetadata')
            ->once()
            ->with($function, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->firstFunctionMetadata($function, $name);

        self::assertEquals($expected, $actual);
    }

    public function testGetPropertyMetadata(): void
    {
        $property = new \ReflectionProperty(self::class, 'readerMock');
        $name = 'test';
        $expected = [];

        $this->readerMock->shouldReceive('getPropertyMetadata')
            ->once()
            ->with($property, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->getPropertyMetadata($property, $name);

        self::assertEquals($expected, $actual);
    }

    public function testFirstPropertyMetadata(): void
    {
        $property = new \ReflectionProperty(self::class, 'readerMock');
        $name = 'test';
        $expected = (object)[];

        $this->readerMock->shouldReceive('firstPropertyMetadata')
            ->once()
            ->with($property, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->firstPropertyMetadata($property, $name);

        self::assertEquals($expected, $actual);
    }

    public function testGetParameterMetadata(): void
    {
        $parameter = new \ReflectionParameter(function($test) {}, 'test');
        $name = 'test';
        $expected = [];

        $this->readerMock->shouldReceive('getParameterMetadata')
            ->once()
            ->with($parameter, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->getParameterMetadata($parameter, $name);

        self::assertEquals($expected, $actual);
    }

    public function testFirstParameterMetadata(): void
    {
        $parameter = new \ReflectionParameter(function($test) {}, 'test');
        $name = 'test';
        $expected = (object)[];

        $this->readerMock->shouldReceive('firstParameterMetadata')
            ->once()
            ->with($parameter, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->firstParameterMetadata($parameter, $name);

        self::assertEquals($expected, $actual);
    }

    public function testGetConstantMetadata(): void
    {
        $constant = new \ReflectionClassConstant(FakeBenSampoEnum::class, 'FOO');
        $name = 'test';
        $expected = [];

        $this->readerMock->shouldReceive('getConstantMetadata')
            ->once()
            ->with($constant, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->getConstantMetadata($constant, $name);

        self::assertEquals($expected, $actual);
    }

    public function testFirstConstantMetadata(): void
    {
        $constant = new \ReflectionClassConstant(FakeBenSampoEnum::class, 'FOO');
        $name = 'test';
        $expected = (object)[];

        $this->readerMock->shouldReceive('firstConstantMetadata')
            ->once()
            ->with($constant, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->firstConstantMetadata($constant, $name);

        self::assertEquals($expected, $actual);
    }

    public function testGetClassMetadata(): void
    {
        $class = new \ReflectionClass(self::class);
        $name = 'test';
        $expected = [];

        $this->readerMock->shouldReceive('getClassMetadata')
            ->once()
            ->with($class, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->getClassMetadata($class, $name);

        self::assertEquals($expected, $actual);
    }

    public function testFirstClassMetadata(): void
    {
        $class = new \ReflectionClass(self::class);
        $name = 'test';
        $expected = (object)[];

        $this->readerMock->shouldReceive('firstClassMetadata')
            ->once()
            ->with($class, $name)
            ->andReturn($expected);

        $actual = $this->annotationReader->firstClassMetadata($class, $name);

        self::assertEquals($expected, $actual);
    }
}
