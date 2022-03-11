<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Route\Parameters\Parameter;

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
     * @return mixed
     * @throws JsonRpcInvalidParameterException
     */
    public function cast(Parameter $parameter, $value, string $fieldName)
    {
        if ($value === null) {
            return null;
        }
    
        try {
            /** @var \BackedEnum $expectedType */
            $expectedType = $parameter->className;
            return $expectedType::from($value);
        } catch (\ValueError $e) {
            throw new JsonRpcInvalidParameterException(
                'incorrect_value',
                $fieldName,
                sprintf(
                    'Invalid value for field. Expected: [%s], Actual: [%s]',
                    implode(',', $expectedType::cases()),
                    $value
                ),
                $e
            );
        } catch (\Throwable $e) {
            throw new JsonRpcInvalidParameterException('error', $fieldName, $e->getMessage(), $e);
        }
    }
}
