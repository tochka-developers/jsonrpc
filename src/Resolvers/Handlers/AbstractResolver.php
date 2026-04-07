<?php

namespace Tochka\JsonRpc\Resolvers\Handlers;


use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterError;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterTypeException;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

abstract class AbstractResolver
{
    abstract public static function canCast(RouteParam $param): bool;
    
    abstract public static function cast(RouteParam $param, JsonRpcRequest $request): mixed;
    
    
    public static function getValue(RouteParam $param, object|array $input): mixed
    {
        if (is_array($input)) {
            if (array_key_exists($param->name, $input)) {
                return $input[$param->name];
            } else {
                return new VoidValue();
            }
        }
        
        if (property_exists($input, $param->name)) {
            return $input->{$param->name};
        }
        
        return new VoidValue();
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function isNullAndAllowNull(RouteParam $param, mixed $value): bool
    {
        if ($value !== null) {
            return false;
        }
        // if null and allow null say true, and null can be returned as value
        if ($param->isNullable) {
            return true;
        }
        
        throw new JsonRpcInvalidParameterException(
            JsonRpcInvalidParameterError::PARAMETER_ERROR_NOT_NULLABLE,
            $param->name,
        );
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function typePassOrThrow(RouteParam $param, mixed $value): void
    {
        if ($param->allowedTypes === []) {
            return;
        }
        
        $type = self::getTypeNormalized($value);
        if (\in_array($type, $param->allowedTypes, true)) {
            return;
        }
        
        throw new JsonRpcInvalidParameterTypeException($param->name, implode('|', $param->allowedTypes), $type);
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public static function isVoidAndAllowVoid(RouteParam $param, mixed $value): bool
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
     * todo for test need test object
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
    
    /**
     * @throws ValidationException
     * @codeCoverageIgnore
     */
    public static function doValidation(object|array $data, array $rules, array $messages = [], array $attributes = []): void
    {
        Validator::make($data, $rules, $messages, $attributes)->validate();
    }
}
