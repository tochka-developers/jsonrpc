<?php

namespace Tochka\JsonRpc\DocBlock;

trait VariableValueTrait
{
    protected static function getRealValue($value)
    {
        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            case '[]':
                return [];
        }
        if (is_numeric($value)) {
            return $value * 1;
        }

        return trim($value, '"');
    }
}