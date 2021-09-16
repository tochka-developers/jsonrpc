<?php

namespace Tochka\JsonRpc\Support;

use BenSampo\Enum\Enum;

/**
 * @method static self PROPERTY()
 * @method static self METHOD()
 * @method static self RETURN()
 */
final class DocBlockTypeEnum extends Enum
{
    public const PROPERTY = 'property';
    public const METHOD = 'method';
    public const RETURN = 'return';
}
