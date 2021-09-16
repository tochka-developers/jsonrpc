<?php

namespace Tochka\JsonRpc\Casters;

use BenSampo\Enum\Enum;
use BenSampo\Enum\Exceptions\InvalidEnumMemberException;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;

class BenSampoEnumCaster implements GlobalCustomCasterInterface
{
    public function canCast(string $expectedType): bool
    {
        return is_subclass_of($expectedType, '\BenSampo\Enum\Enum');
    }
    
    /**
     * @throws JsonRpcException
     */
    public function cast(string $expectedType, $value, string $fieldName): ?Enum
    {
        if ($value === null) {
            return null;
        }
        
        try {
            /** @var Enum $expectedType */
            return $expectedType::fromValue($value);
        } catch (InvalidEnumMemberException $e) {
            throw new JsonRpcInvalidParameterException(
                'incorrect_value',
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
