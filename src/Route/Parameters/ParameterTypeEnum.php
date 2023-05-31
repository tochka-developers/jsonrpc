<?php

namespace Tochka\JsonRpc\Route\Parameters;

use BenSampo\Enum\Enum;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\String_;

/**
 * @method static self TYPE_STRING()
 * @method static self TYPE_FLOAT()
 * @method static self TYPE_BOOLEAN()
 * @method static self TYPE_INTEGER()
 * @method static self TYPE_OBJECT()
 * @method static self TYPE_ARRAY()
 * @method static self TYPE_MIXED()
 */
final class ParameterTypeEnum extends Enum
{
    public const TYPE_STRING = 'string';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_OBJECT = 'object';
    public const TYPE_ARRAY = 'array';
    public const TYPE_MIXED = 'mixed';
    
    public static function fromReflectionType(\ReflectionNamedType $type): self
    {
        if (!$type->isBuiltin()) {
            return self::TYPE_OBJECT();
        }
        
        switch ($type->getName()) {
            case 'string':
                return self::TYPE_STRING();
            case 'float':
                return self::TYPE_FLOAT();
            case 'bool':
                return self::TYPE_BOOLEAN();
            case 'int':
                return self::TYPE_INTEGER();
            case 'array':
                return self::TYPE_ARRAY();
            case 'object':
                return self::TYPE_OBJECT();
            default:
                return self::TYPE_MIXED();
        }
    }
    
    public static function fromDocBlockType(Type $type): self
    {
        switch (true) {
            case $type instanceof String_:
                return self::TYPE_STRING();
            case $type instanceof Float_:
                return self::TYPE_FLOAT();
            case $type instanceof Boolean:
                return self::TYPE_BOOLEAN();
            case $type instanceof Integer:
                return self::TYPE_INTEGER();
            case $type instanceof Array_:
                return self::TYPE_ARRAY();
            case $type instanceof Object_:
                return self::TYPE_OBJECT();
            default:
                return self::TYPE_MIXED();
        }
    }
    
    public static function fromVarType(string $varType): self
    {
        switch ($varType) {
            case 'string':
                return self::TYPE_STRING();
            case 'double':
                return self::TYPE_FLOAT();
            case 'boolean':
                return self::TYPE_BOOLEAN();
            case 'integer':
                return self::TYPE_INTEGER();
            case 'array':
                return self::TYPE_ARRAY();
            case 'object':
                return self::TYPE_OBJECT();
            default:
                return self::TYPE_MIXED();
        }
    }
    
    public static function __set_state(array $enum): static
    {
        return self::coerce($enum['value']) ?? self::TYPE_MIXED();
    }
    
    public function toJsonType(): string
    {
        switch ($this->value) {
            case self::TYPE_STRING:
                return 'string';
            case self::TYPE_FLOAT:
                return 'number';
            case self::TYPE_BOOLEAN:
                return 'boolean';
            case self::TYPE_INTEGER:
                return 'integer';
            case self::TYPE_ARRAY:
                return 'array';
            case self::TYPE_OBJECT:
                return 'object';
            default:
                return 'any';
        }
    }
}
