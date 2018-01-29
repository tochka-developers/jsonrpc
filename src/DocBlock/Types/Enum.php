<?php

namespace Tochka\JsonRpc\DocBlock\Types;

use Tochka\JsonRpc\DocBlock\VariableValueTrait;
use phpDocumentor\Reflection\Type;

/**
 * Class Enum
 * @package Tochka\JsonRpc\DocBlock\Types
 */
class Enum implements Type
{
    use VariableValueTrait;

    protected $variants;

    public function __construct($variants = null)
    {
        if (preg_match('/^\(.*\)$/iu', $variants)) {
            $variants = trim($variants, '()');

            preg_match_all('/(?<values>\"[^\"]*\"|[^,]+)/iu', $variants, $matches, PREG_PATTERN_ORDER);

            $variants = [];
            foreach ($matches['values'] as $value) {
                $variants[] = self::getRealValue($value);
            }
        }

        $this->variants = $variants;
    }

    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * Returns a rendered output of the Type as it would be used in a DocBlock.
     *
     * @return string
     */
    public function __toString()
    {
        return 'enum';
    }
}