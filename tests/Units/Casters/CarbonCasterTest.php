<?php

namespace Tochka\JsonRpc\Tests\Units\Casters;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Annotations\TimeZone;
use Tochka\JsonRpc\Casters\CarbonCaster;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;

/**
 * @covers \Tochka\JsonRpc\Casters\CarbonCaster
 */
class CarbonCasterTest extends TestCase
{
    use MakeParameterTrait;

    private CarbonCaster $caster;

    public function setUp(): void
    {
        parent::setUp();

        $this->caster = new CarbonCaster();
    }

    public function testCanCastTrue(): void
    {
        $actual = $this->caster->canCast(Carbon::class);

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
        $parameter = $this->makeParameter(Carbon::class);

        $actual = $this->caster->cast($parameter, null, 'test');

        self::assertNull($actual);
    }

    public function testCastInvalidValueType(): void
    {
        $parameter = $this->makeParameter(Carbon::class);

        self::expectException(InvalidParameterException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS);

        $this->caster->cast($parameter, [], 'test');
    }

    public function testCastInvalidValue(): void
    {
        $parameter = $this->makeParameter(Carbon::class);

        self::expectException(InvalidParameterException::class);
        self::expectExceptionMessage(AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS);

        $this->caster->cast($parameter, 'Invalid data', 'test');
    }

    public function testCastCorrectValue(): void
    {
        $dateTime = '2022-03-23 12:02:10';

        $parameter = $this->makeParameter(Carbon::class);

        $actual = $this->caster->cast($parameter, $dateTime, 'test');

        self::assertEquals(Carbon::parse($dateTime), $actual);
    }

    public function testCastCorrectValueWithTimezone(): void
    {
        $dateTime = '2022-03-23 12:02:10';
        $timezone = 'Europe/Moscow';

        $parameter = $this->makeParameter(Carbon::class);
        $parameter->annotations[] = new TimeZone($timezone);

        $actual = $this->caster->cast($parameter, $dateTime, 'test');

        self::assertEquals(Carbon::parse($dateTime, $timezone), $actual);
    }
}
