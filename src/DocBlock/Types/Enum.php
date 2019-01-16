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
    protected $type;

    public function __construct($variantsStr = null)
    {
        $this->type = 'string';

        $variants = [];

        if (preg_match('/^\(.*\)$/u', $variantsStr)) {
            $variantsStr = trim($variantsStr, '()');

            preg_match_all('/(?<values>\"[^\"]*\"|[^,]+)/iu', $variantsStr, $matches, PREG_PATTERN_ORDER);

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
        }

        $this->variants = $variants;
    }

    public function getVariants(): array
    {
        return $this->variants;
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