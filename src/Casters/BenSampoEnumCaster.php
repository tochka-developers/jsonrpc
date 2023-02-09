<?php

namespace Tochka\JsonRpc\Casters;

use BenSampo\Enum\Enum;
use BenSampo\Enum\Exceptions\InvalidEnumMemberException;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\InvalidEnumValueException;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;

class BenSampoEnumCaster implements GlobalCustomCasterInterface
{
    public function canCast(string $expectedType): bool
    {
        return class_exists('\\BenSampo\\Enum\\Enum') && is_a($expectedType, Enum::class, true);
    }

    public function cast(Parameter $parameter, mixed $value, string $fieldName): ?Enum
    {
        if ($value === null) {
            return null;
        }

        /** @var class-string<Enum>|null $expectedType */
        $expectedType = $parameter->className;
        if ($expectedType === null || !is_a($expectedType, Enum::class, true)) {
            throw new InternalErrorException();
        }

        try {
            return $expectedType::fromValue($value);
        } catch (InvalidEnumMemberException $e) {
            throw new InvalidEnumValueException($fieldName, $value, $expectedType::getValues(), $e);
        }
    }
}
