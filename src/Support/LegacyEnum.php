<?php

namespace Tochka\JsonRpc\Support;

abstract class LegacyEnum
{
    private string $value;

    protected function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function is(self $enum): bool
    {
        return $this->value === $enum->value;
    }

    public function isNot(self $enum): bool
    {
        return !$this->is($enum);
    }

    /**
     * @param array{value: string} $array
     */
    public static function __set_state(array $array): static
    {
        /** @psalm-suppress UnsafeInstantiation */
        return new static($array['value']);
    }
}
