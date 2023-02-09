<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\InvalidEnumValueException;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;

/**
 * @since 8.1
 */
class EnumCaster implements GlobalCustomCasterInterface
{
    public function canCast(string $expectedType): bool
    {
        return function_exists('enum_exists') && enum_exists($expectedType);
    }

    /**
     * @param Parameter $parameter
     * @param mixed $value
     * @param string $fieldName
     * @return \BackedEnum|null
     */
    public function cast(Parameter $parameter, mixed $value, string $fieldName): ?object
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) && !is_int($value)) {
            throw InvalidParameterException::from(
                parameterName: $fieldName,
                code:          InvalidParameterError::CODE_INCORRECT_VALUE,
            );
        }

        /** @var class-string<\BackedEnum>|null $expectedType */
        $expectedType = $parameter->className;
        if ($expectedType === null || !enum_exists($expectedType)) {
            throw new InternalErrorException();
        }

        try {
            return $expectedType::from($value);
        } catch (\ValueError $e) {
            throw new InvalidEnumValueException($fieldName, $value, $expectedType::cases(), $e);
        }
    }
}
