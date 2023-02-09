<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Illuminate\Container\Container;
use Tochka\JsonRpc\Contracts\CustomCasterInterface;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\CasterRegistry;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\CasterRegistry
 */
class CasterRegistryTest extends DefaultTestCase
{
    private CasterRegistry $registry;

    public function setUp(): void
    {
        parent::setUp();

        $this->registry = new CasterRegistry(Container::getInstance());
    }

    public function testCastViaGlobalCaster(): void
    {
        $parameter = new Parameter('foo', ParameterTypeEnum::TYPE_OBJECT());
        $value = 'foo';
        $fieldName = 'bar';
        $expected = (object)['foo' => 'bar'];

        $fooCaster = \Mockery::mock('FooCaster', GlobalCustomCasterInterface::class);
        $fooCaster->shouldReceive('cast')
            ->never();

        $barCaster = \Mockery::mock('BarCaster', GlobalCustomCasterInterface::class);
        $barCaster->shouldReceive('cast')
            ->once()
            ->with($parameter, $value, $fieldName)
            ->andReturn($expected);

        $this->registry->addCaster($fooCaster);
        $this->registry->addCaster($barCaster);

        $actual = $this->registry->cast($barCaster::class, $parameter, $value, $fieldName);

        self::assertEquals($expected, $actual);
    }

    public function testCastViaCaster(): void
    {
        $parameter = new Parameter('foo', ParameterTypeEnum::TYPE_OBJECT());
        $value = 'foo';
        $fieldName = 'bar';
        $expected = (object)['foo' => 'bar'];

        $fakeCaster = \Mockery::mock('BarCaster', CustomCasterInterface::class);
        $fakeCaster->shouldReceive('cast')
            ->once()
            ->with($parameter, $value, $fieldName)
            ->andReturn($expected);

        $container = Container::getInstance();
        $container->instance($fakeCaster::class, $fakeCaster);

        $actual = $this->registry->cast($fakeCaster::class, $parameter, $value, $fieldName);

        self::assertEquals($expected, $actual);
    }

    public function testCastViaCasterBindingError(): void
    {
        $parameter = new Parameter('foo', ParameterTypeEnum::TYPE_OBJECT());
        $value = 'foo';
        $fieldName = 'bar';

        self::expectException(InternalErrorException::class);
        self::expectExceptionMessage(JsonRpcException::MESSAGE_INTERNAL_ERROR);

        $this->registry->cast('UnknownCaster', $parameter, $value, $fieldName);
    }

    public function testCastViaCasterInvalidType(): void
    {
        $parameter = new Parameter('foo', ParameterTypeEnum::TYPE_OBJECT());
        $value = 'foo';
        $fieldName = 'bar';

        $fakeCaster = \Mockery::mock('BarCaster');

        $container = Container::getInstance();
        $container->instance($fakeCaster::class, $fakeCaster);

        self::expectException(InternalErrorException::class);
        self::expectExceptionMessage(
            sprintf('Caster [%s] must implement [%s]', $fakeCaster::class, CustomCasterInterface::class)
        );

        $this->registry->cast($fakeCaster::class, $parameter, $value, $fieldName);
    }

    public function testGetCasterForClassExists(): void
    {
        $className = 'TestClass';

        $fooCaster = \Mockery::mock('FooCaster', GlobalCustomCasterInterface::class);
        $fooCaster->shouldReceive('canCast')
            ->once()
            ->with($className)
            ->andReturnFalse();

        $expectedCaster = \Mockery::mock('BarCaster', GlobalCustomCasterInterface::class);
        $expectedCaster->shouldReceive('canCast')
            ->once()
            ->with($className)
            ->andReturnTrue();

        $barCaster = \Mockery::mock('ThirdCaster', GlobalCustomCasterInterface::class);
        $barCaster->shouldReceive('canCast')
            ->never()
            ->with($className);

        $this->registry->addCaster($fooCaster);
        $this->registry->addCaster($expectedCaster);
        $this->registry->addCaster($barCaster);

        $actual = $this->registry->getCasterForClass($className);

        self::assertEquals($expectedCaster::class, $actual);
    }

    public function testGetCasterForClassNotExists(): void
    {
        $className = 'TestClass';

        $fooCaster = \Mockery::mock('FooCaster', GlobalCustomCasterInterface::class);
        $fooCaster->shouldReceive('canCast')
            ->once()
            ->with($className)
            ->andReturnFalse();

        $barCaster = \Mockery::mock('BarCaster', GlobalCustomCasterInterface::class);
        $barCaster->shouldReceive('canCast')
            ->once()
            ->with($className)
            ->andReturnFalse();

        $this->registry->addCaster($fooCaster);
        $this->registry->addCaster($barCaster);

        $actual = $this->registry->getCasterForClass($className);

        self::assertNull($actual);
    }
}
