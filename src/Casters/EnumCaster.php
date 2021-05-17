<?php

namespace Tochka\JsonRpc\Casters;

use BenSampo\Enum\Enum;
use BenSampo\Enum\Exceptions\InvalidEnumMemberException;
use Tochka\JsonRpc\Contracts\GlobalPropertyCasterInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

class EnumCaster implements GlobalPropertyCasterInterface
{
    public function canCast(string $expectedType, $value, \ReflectionProperty $property, string $fieldName): bool
    {
        return is_subclass_of($expectedType, '\BenSampo\Enum\Enum');
    }
    
    /**
     * @throws JsonRpcException
     */
    public function cast(string $expectedType, $value, \ReflectionProperty $property, string $fieldName): ?Enum
    {
        if ($value === null) {
            return null;
        }
        
        try {
            /** @var Enum $expectedType */
            return $expectedType::fromValue($value);
        } catch (InvalidEnumMemberException $e) {
            throw new JsonRpcException(
                JsonRpcException::CODE_INVALID_PARAMETERS,
                sprintf(
                    'Invalid value for field. Expected: [%s], Actual: [%s]',
                    implode(',', $expectedType::getValues()),
                    $value
                ),
                $fieldName
            );
        }
    }
}
