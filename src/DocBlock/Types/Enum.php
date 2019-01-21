<?php

namespace Tochka\JsonRpc\DocBlock\Types;

use phpDocumentor\Reflection\Type;
use Tochka\JsonRpc\DocBlock\VariableValueTrait;

/**
 * Class Enum
 * @package Tochka\JsonRpc\DocBlock\Types
 */
class Enum implements Type
{
    use VariableValueTrait;

    protected $variants;
    protected $hasVariants;
    protected $variantsType;
    protected $type;

    public function __construct($variantsStr = null)
    {
        $this->type = 'string';

        if (preg_match('/^\(.*\)$/u', $variantsStr)) {
            $this->hasVariants = true;
            $variants = [];

            $variantsStr = trim($variantsStr, '()');

            preg_match_all(/** @lang text */'/(?<values>\"[^\"]*\"|[^,]+)/iu', $variantsStr, $matches, PREG_PATTERN_ORDER);

            $type = 0;

            foreach ($matches['values'] as $value) {
                $realValue = self::getRealValue($value);

                switch (true) {
                    case \is_int($realValue):
                        break;
                    case \is_float($realValue):
                        if ($type < 2) {
                            $type = 1;
                        }
                        break;
                    default:
                        $type = 2;
                }

                $variants[] = $realValue;
            }

            switch ($type) {
                case 0:
                    $this->type = 'int';
                    break;
                case 1:
                    $this->type = 'float';
                    break;
                default:
                    $this->type = 'string';
            }

            $this->variants = $variants;
        } else {
            $this->hasVariants = false;
            $this->variantsType = $variantsStr;
        }
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function hasVariants(): bool
    {
        return $this->hasVariants;
    }

    public function getVariantsType(): ?string
    {
        return $this->variantsType;
    }

    public function getRealType(): string
    {
        return $this->type;
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