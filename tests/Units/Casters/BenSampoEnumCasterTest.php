<?php

namespace Tochka\JsonRpc\Tests\Units\Casters;

use BenSampo\Enum\Enum;
use Tochka\JsonRpc\Casters\BenSampoEnumCaster;
use Tochka\JsonRpc\Exceptions\InvalidEnumValueException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Tests\Stubs\FakeBenSampoEnum;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Casters\BenSampoEnumCaster
 */
class BenSampoEnumCasterTest extends DefaultTestCase
{
    use MakeParameterTrait;

    private BenSampoEnumCaster $caster;

    public function setUp(): void
    {
        parent::setUp();

        $this->caster = new BenSampoEnumCaster();
    }

    public function testCanCastTrue(): void
    {
        $object = \Mockery::mock(Enum::class);

        $actual = $this->caster->canCast($object::class);

        self::assertTrue($actual);
    }

    public function testCanCastFalse(): void
    {
        $object = \Mockery::mock();

        $actual = $this->caster->canCast($object::class);

        self::assertFalse($actual);
    }

    public function testCastNull(): void
    {
        $object = \Mockery::mock();
        $parameter = $this->makeParameter($object::class);

        $actual = $this->caster->cast($parameter, null, 'test');

        self::assertNull($actual);
    }

    public function testCastEmptyExpectedType(): void
    {
        $parameter = $this->makeParameter(null);

        self::expectException(InternalErrorException::class);
        self::expectExceptionMessage(JsonRpcException::MESSAGE_INTERNAL_ERROR);

        $this->caster->cast($parameter, 'foo', 'test');
    }

    public function testCastInvalidExpectedType(): void
    {
        $object = \Mockery::mock();
        $parameter = $this->makeParameter($object::class);

        self::expectException(InternalErrorException::class);
        self::expectExceptionMessage(JsonRpcException::MESSAGE_INTERNAL_ERROR);

        $this->caster->cast($parameter, 'foo', 'test');
    }

    public function testCastInvalidValue(): void
    {
        $parameter = $this->makeParameter(FakeBenSampoEnum::class);

        self::expectException(InvalidEnumValueException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS);

        $this->caster->cast($parameter, 'invalid', 'test');
    }

    public function testCastCorrectValue(): void
    {
        $parameter = $this->makeParameter(FakeBenSampoEnum::class);

        $actual = $this->caster->cast($parameter, 'foo', 'test');

        self::assertEquals(FakeBenSampoEnum::FOO(), $actual);
    }
}
