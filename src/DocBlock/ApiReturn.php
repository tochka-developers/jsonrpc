<?php

namespace Tochka\JsonRpc\DocBlock;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\StaticMethod;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use Tochka\JsonRpc\DocBlock\TypeResolver as CustomTypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use Webmozart\Assert\Assert;

/**
 * Reflection class for the {@}apiReturn tag in a Docblock.
 */
class ApiReturn extends BaseTag implements StaticMethod
{
    const REGEXP = '/(((?<type>[a-z\[\]]+)(\=(?<typeFormat>[a-z0-9]+|"[^"]+"|\([^\)]+\)))?) +)(\$(?<variableName>[a-z\._0-9\[\]]+)[ \n]+)(?<description>.*)/is';

    /** @var string */
    protected $name = 'apiReturn';

    /** @var Type */
    protected $type;

    /** @var string */
    protected $variableName = '';

    /**
     * @param string $variableName
     * @param Type $type
     * @param bool $isVariadic
     * @param Description $description
     */
    public function __construct($variableName, Type $type = null, Description $description = null)
    {
        Assert::string($variableName);

        $this->variableName = $variableName;
        $this->type = $type;
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(
        $body,
        TypeResolver $typeResolver = null,
        DescriptionFactory $descriptionFactory = null,
        TypeContext $context = null
    )
    {
        Assert::stringNotEmpty($body);
        Assert::allNotNull([$typeResolver, $descriptionFactory]);

        preg_match(self::REGEXP, $body, $parts);

        $description = $descriptionFactory->create(trim($parts['description']), $context);
        $type = CustomTypeResolver::resolve(trim($parts['type']), $parts['typeFormat']);

        /** @var static $param */
        return new static($parts['variableName'], $type, $description);
    }

    /**
     * Returns the variable's name.
     *
     * @return string
     */
    public function getVariableName()
    {
        return $this->variableName;
    }

    /**
     * Returns the variable's type or null if unknown.
     *
     * @return Type|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns a string representation for this tag.
     *
     * @return string
     */
    public function __toString()
    {
        return '';
    }
}
