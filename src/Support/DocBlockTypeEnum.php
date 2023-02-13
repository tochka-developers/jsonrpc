<?php

namespace Tochka\JsonRpc\Support;

final class DocBlockTypeEnum extends LegacyEnum
{
    public const PROPERTY = 'property';
    public const METHOD = 'method';
    public const RETURN = 'return';

    /**
     * @codeCoverageIgnore
     */
    public static function PROPERTY(): self
    {
        return new self(self::PROPERTY);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function METHOD(): self
    {
        return new self(self::METHOD);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function RETURN(): self
    {
        return new self(self::RETURN);
    }
}
