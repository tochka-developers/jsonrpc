<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Tochka\JsonRpc\Support\LegacyEnum;
use Tochka\JsonRpc\Tests\Stubs\FakeLegacyEnum;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\LegacyEnum
 */
class LegacyEnumTest extends DefaultTestCase
{
    public function test__set_state(): void
    {
        $enum = FakeLegacyEnum::__set_state(['value' => 'foo']);

        self::assertEquals(FakeLegacyEnum::FOO(), $enum);
    }

    public function testGetValue(): void
    {
        $enum = FakeLegacyEnum::FOO();

        self::assertEquals(FakeLegacyEnum::FOO, $enum->getValue());
    }

    public function testIsNot(): void
    {
        $enum = FakeLegacyEnum::FOO();

        self::assertTrue($enum->isNot(FakeLegacyEnum::BAR()));
        self::assertFalse($enum->isNot(FakeLegacyEnum::FOO()));
    }

    public function testIs(): void
    {
        $enum = FakeLegacyEnum::FOO();

        self::assertFalse($enum->is(FakeLegacyEnum::BAR()));
        self::assertTrue($enum->is(FakeLegacyEnum::FOO()));
    }
}
