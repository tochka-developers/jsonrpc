<?php

namespace Tochka\JsonRpc\Casters;

use BenSampo\Enum\Enum;
use BenSampo\Enum\Exceptions\InvalidEnumMemberException;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Route\Parameters\Parameter;

class BenSampoEnumCaster implements GlobalCustomCasterInterface
{
    public function canCast(string $expectedType): bool
    {
        return class_exists('\\BenSampo\\Enum\\Enum') && is_a($expectedType, '\\BenSampo\\Enum\\Enum', true);
    }
    
    /**
     * @throws JsonRpcException
     */
    public function cast(Parameter $parameter, $value, string $fieldName): ?Enum
    {
        if ($value === null) {
            return null;
        }
        
        try {
            /** @var Enum $expectedType */
            $expectedType = $parameter->className;
            return $expectedType::fromValue($value);
        } catch (InvalidEnumMemberException $e) {
            throw new JsonRpcInvalidParameterException(
                'incorrect_value',
                $fieldName,
                sprintf(
                    'Invalid value for field. Expected: [%s], Actual: [%s]',
                    implode(',', $expectedType::getValues()),
                    $value
                ),
                $e
            );
        } catch (\Throwable $e) {
            throw new JsonRpcInvalidParameterException('error', $fieldName, $e->getMessage(), $e);
        }
    }
}
