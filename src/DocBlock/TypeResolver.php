<?php

namespace Tochka\JsonRpc\DocBlock;

use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\String_;
use Tochka\JsonRpc\DocBlock\Types\Date;
use Tochka\JsonRpc\DocBlock\Types\Enum;
use Tochka\JsonRpc\DocBlock\Types\Object_;

class TypeResolver
{
    protected static $keywords = [
        'string'      => String_::class,
        'int'         => Integer::class,
        'integer'     => Integer::class,
        'bool'        => Boolean::class,
        'boolean'     => Boolean::class,
        'float'       => Float_::class,
        'double'      => Float_::class,
        'object'      => Object_::class,
        'mixed'       => Mixed_::class,
        'array'       => Array_::class,
        'date'        => Date::class,
        'datetime'    => Date::class,
        'enum'        => Enum::class,
        'enumeration' => Enum::class,
    ];

    public static function resolve($type, $extended = null)
    {
        $lType = strtolower($type);

        // проверим, вдруг это массив
        if (substr($type, -2) === '[]') {
            $type = static::resolve(substr($type, 0, -2), $extended);

            return new Array_($type);
        }

        // проверим специальные типы
        if (isset(self::$keywords[$lType])) {
            if (\in_array(self::$keywords[$lType], [Date::class, Enum::class], true)) {
                return new self::$keywords[$lType]($extended);
            }

            return new self::$keywords[$lType]();
        }

        return new Object_($type);
    }
}