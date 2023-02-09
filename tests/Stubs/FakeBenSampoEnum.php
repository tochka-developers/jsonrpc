<?php

namespace Tochka\JsonRpc\Tests\Stubs;

use BenSampo\Enum\Enum;

/**
 * @method static self FOO()
 * @method static self BAR()
 */
final class FakeBenSampoEnum extends Enum
{
    public const FOO = 'foo';
    public const BAR = 'bar';
}
