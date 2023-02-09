<?php

namespace Tochka\JsonRpc\Tests\Units\Casters;

use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Casters\EnumCaster;
use Tochka\JsonRpc\Exceptions\InvalidEnumValueException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Tests\Stubs\FakeEnum;

/**
 * @covers \Tochka\JsonRpc\Casters\EnumCaster
 */
class EnumCasterTest extends TestCase
{
    use MakeParameterTrait;

    private EnumCaster $caster;

    public function setUp(): void
    {
        parent::setUp();

        $this->caster = new EnumCaster();
    }

    public function testCanCastTrue(): void
    {
        $actual = $this->caster->canCast(FakeEnum::class);

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
        $parameter = $this->makeParameter(FakeEnum::class);

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

    public function testCastInvalidValueType(): void
    {
        $parameter = $this->makeParameter(FakeEnum::class);

        self::expectException(InvalidParameterException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS);

        $this->caster->cast($parameter, [], 'test');
    }

    public function testCastInvalidValue(): void
    {
        $parameter = $this->makeParameter(FakeEnum::class);

        self::expectException(InvalidEnumValueException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS);

        $this->caster->cast($parameter, 'invalid', 'test');
    }

    public function testCastCorrectValue(): void
    {
        $parameter = $this->makeParameter(FakeEnum::class);

        $actual = $this->caster->cast($parameter, 'foo', 'test');

        self::assertEquals(FakeEnum::FOO, $actual);
    }
}
