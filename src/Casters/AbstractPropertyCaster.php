<?php

namespace Tochka\JsonRpc\Casters;

use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterError;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterTypeException;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\VoidValue;

abstract class AbstractPropertyCaster
{
    abstract public static function canCast(RouteParam $param): bool;
    
    abstract public static function cast(RouteParam $param, object $input): mixed;
    
    
    public static function getValue(RouteParam $param, object|array $input): mixed
    {
        if (is_array($input)) {
            return $input[$param->name] ?? new VoidValue();
        }
        
        if (property_exists($input, $param->name)) {
            return $input->{$param->name};
        }
        
        return new VoidValue();
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function typeCheck(RouteParam $param, mixed $value): bool
    {
        if ($value === null) {
            if ($param->isNullable) {
                return true;
            }
            throw new JsonRpcInvalidParameterException(
                JsonRpcInvalidParameterError::PARAMETER_ERROR_NOT_NULLABLE,
                $param->name,
            );
        }
        
        if (empty($param->allowedTypes)) {
            return false;
        }
        
        $type = self::getTypeNormalized($value);
        if (\in_array($type, $param->allowedTypes, true)) {
            return false;
        }
        
        throw new JsonRpcInvalidParameterTypeException($param->name, implode('|', $param->allowedTypes), $type);
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function optionalCheck(RouteParam $param, mixed $value): bool
    {
        if ($value instanceof VoidValue) {
            if ($param->isOptional) {
                return true;
            } else {
                throw new JsonRpcInvalidParameterException(
                    JsonRpcInvalidParameterError::PARAMETER_ERROR_REQUIRED, $param->name
                );
            }
        }
        return false;
    }
    
    /**
     * @throws \ReflectionException
     * @throws JsonRpcInvalidParameterException
     */
    public static function createObjectWithoutConstructor(RouteParam $param, array|object $data): object
    {
        $dataAsArray = (array)$data;
        $reflector = new \ReflectionClass($param->className);
        $object = $reflector->newInstanceWithoutConstructor();
        foreach ($dataAsArray as $propName => $propValue) {
            try {
                $reflector->getProperty($propName)?->setValue($object, $propValue);
            } catch (\ReflectionException) {
                // Property not exist, just skip
                continue;
            } catch (\TypeError) {
                throw new JsonRpcInvalidParameterTypeException(
                    $propName,
                    $reflector->getProperty($propName)->getType(),
                    self::getTypeNormalized($propValue),
                );
            }
        }
        
        return $object;
    }
    
    public static function getTypeNormalized(mixed $value): string
    {
        $type = gettype($value);
        return match ($type) {
            'boolean' => 'bool',
            'integer' => 'int',
            'double' => 'float',
            'NULL' => 'null',
            default => $type,
        };
    }
}
