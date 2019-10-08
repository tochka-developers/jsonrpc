<?php

namespace Tochka\JsonRpc\DocBlock\Types;

use phpDocumentor\Reflection\Type;

/**
 * Class Object_
 * @package Tochka\JsonRpc\DocBlock\Types
 */
class Object_ implements Type
{
    /** @var string */
    protected $className;

    public function __construct($className = null)
    {
        $this->className = $className;
    }

    /**
     * Возвращает имя класса
     *
     * @return string
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Returns a rendered output of the Type as it would be used in a DocBlock.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->className) {
            return (string)$this->className;
        }

        return 'object';
    }
}