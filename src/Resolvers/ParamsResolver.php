<?php

namespace Tochka\JsonRpc\Resolvers;

use BackedEnum;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterError;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParametersException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterTypeException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;
use Tochka\JsonRpc\Traits\WithDataMap;

class ParamsResolver
{
    protected JsonRpcRequest $request;
    
    public function __construct(JsonRpcRequest $request)
    {
        $this->request = $request;
    }
    
    
    /**
     * @param array<string, RouteParam> $params
     * @param array|object $rawData
     * @return array<string, mixed>
     * @throws JsonRpcException
     * @throws JsonRpcInvalidParameterException
     * @throws JsonRpcInvalidParameterTypeException
     * @throws JsonRpcInvalidParametersException
     */
    public function resolve(array $params, array|object $rawData): array
    {
        /** @var array<string, mixed> $result */
        $result = [];
        $errors = [];
        try {
            foreach ($params as $parameter) {
                $rawValue = self::getValue($parameter, $rawData);
                match ($parameter->propType) {
                    PropType::DI => $value = $this->resolveDI($parameter),
                    PropType::RequestObject => $value = $this->resolveRequestObject($parameter, $rawValue),
                    PropType::Mixed => $value = $this->resolveMixed($parameter, $rawValue),
                    
                    PropType::Object => $value = $this->resolveObject($parameter, $rawValue),
                    PropType::Enum => $value = $this->resolveEnum($parameter, $rawValue),
                    PropType::Primitive => $value = $this->resolvePrimitive($parameter, $rawValue),
                };
                // skip void, will be used default value
                if ($value instanceof VoidValue) {
                    continue;
                }
                $result[$parameter->name] = $value;
            }
        } catch (JsonRpcInvalidParametersException $e) {
            $errors[] = $e->getErrors();
        } catch (JsonRpcException $e) {
            throw $e;
        } catch (\Throwable $t) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR, null, null, $t);
        }
        
        if (!empty($errors)) {
            throw new JsonRpcInvalidParametersException(array_merge(...$errors));
        }
        
        return $result;
    }
    
    /**
     * @throws JsonRpcException
     */
    protected function resolveDI(RouteParam $param): mixed
    {
        if ($param->className === JsonRpcRequest::class) {
            return $this->request;
        }
        try {
            return Container::getInstance()->make($param->className);
        } catch (BindingResolutionException $e) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR, null, null, $e);
        }
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    protected function resolveMixed(RouteParam $param, mixed $rawValue): mixed
    {
        if (self::isVoidAndAllowVoid($param, $rawValue)) {
            return new VoidValue();
        }
        
        return $rawValue;
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    protected function resolveEnum(RouteParam $param, mixed $rawValue): VoidValue|BackedEnum|null
    {
        if (self::isVoidAndAllowVoid($param, $rawValue)) {
            return new VoidValue();
        }
        
        if (self::isNullAndAllowNull($param, $rawValue)) {
            return null;
        }
        
        self::typePassOrThrow($param, $rawValue);
        
        /** @var \BackedEnum $className */
        $className = $param->className;
        
        try {
            return $className::from($rawValue);
        } catch (\ValueError) {
            $expected = implode(',', array_map(fn($enum) => (string)$enum->value, $className::cases()));
            throw new JsonRpcInvalidParameterException(
                'incorrect_value',
                $param->name,
                sprintf('Invalid value for field. Expected: [%s], Actual: [%s]', $expected, $rawValue),
            );
        }
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    protected function resolvePrimitive(RouteParam $param, mixed $rawValue): mixed
    {
        if (self::isVoidAndAllowVoid($param, $rawValue)) {
            return new VoidValue();
        }
        
        if (self::isNullAndAllowNull($param, $rawValue)) {
            return $rawValue;
        }
        
        self::typePassOrThrow($param, $rawValue);
        
        return $rawValue;
    }
    
    /**
     * @throws JsonRpcException
     * @throws JsonRpcInvalidParametersException
     * @throws JsonRpcInvalidParameterTypeException
     * @throws JsonRpcInvalidParameterException
     * @throws \ReflectionException
     */
    protected function resolveObject(RouteParam $param, mixed $rawValue): mixed
    {
        if (self::isVoidAndAllowVoid($param, $rawValue)) {
            return new VoidValue();
        }
        
        if (self::isNullAndAllowNull($param, $rawValue)) {
            return null;
        }
        
        self::typePassOrThrow($param, $rawValue);
        
        $class = $param->className;
        $uses = class_uses_recursive($class);
        
        if (\in_array(WithDataMap::class, $uses)) {
            /** @var WithDataMap $class */
            return $class::dataMap($param, $rawValue);
        }
        
        $innerParams = $this->resolve($param->params, $rawValue);
        return self::createObjectFromPreparedProps($param, $innerParams);
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     * @throws JsonRpcInvalidParameterTypeException
     * @throws JsonRpcException
     * @throws \ReflectionException
     * @throws JsonRpcInvalidParametersException
     */
    protected function resolveRequestObject(RouteParam $param, mixed $rawValue): object|null
    {
        $class = $param->className;
        
        $uses = class_uses_recursive($class);
        
        if (\in_array(WithDataMap::class, $uses)) {
            /** @var WithDataMap $class */
            return $class::dataMap($param, $rawValue);
        }
        
        return self::createObjectFromPreparedProps($param, $this->resolve($param->params, $rawValue));
    }
    
    
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
     * @throws JsonRpcInvalidParameterTypeException
     */
    public static function createObjectFromPreparedProps(RouteParam $param, array $props): object
    {
        $reflector = new \ReflectionClass($param->className);
        $object = $reflector->newInstanceWithoutConstructor();
        foreach ($props as $propName => $propValue) {
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
