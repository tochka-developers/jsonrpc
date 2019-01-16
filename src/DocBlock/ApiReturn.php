<?php

namespace Tochka\JsonRpc\DocBlock;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\StaticMethod;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use Tochka\JsonRpc\DocBlock\TypeResolver as CustomTypeResolver;
use Webmozart\Assert\Assert;

/**
 * Reflection class for the {@}apiReturn tag in a Docblock.
 */
class ApiReturn extends BaseTag implements StaticMethod
{
    protected const REGEXP = '/((?<is_root>\*) +)?(((?<type>[a-z\[\]]+)(\=(?<typeFormat>[a-z0-9]+|"[^"]+"|\([^\)]+\)))?) +)(\$(?<variableName>[a-z\._0-9\[\]]+)[ \n]+)(?<description>.*)?/is';
    protected const TAG_NAME = 'apiReturn';

    /** @var Type */
    protected $type;

    /** @var string */
    protected $variableName = '';

    /** @var bool */
    protected $is_root = false;

    /**
     * @param string $variableName
     * @param Type $type
     * @param Description $description
     * @param bool $is_root
     */
    public function __construct($variableName, Type $type = null, Description $description = null, $is_root = false)
    {
        Assert::string($variableName);

        $this->name = self::TAG_NAME;
        $this->variableName = $variableName;
        $this->type = $type;
        $this->description = $description;
        $this->is_root = $is_root;
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

        $description = null;

        if (null !== $descriptionFactory) {
            $descriptionStr = isset($parts['description']) ? trim($parts['description']) : '';
            $description = $descriptionFactory->create($descriptionStr, $context);
        }

        $type = CustomTypeResolver::resolve(trim($parts['type']), $parts['typeFormat']);

        /** @var static $param */
        return new static($parts['variableName'], $type, $description, !empty($parts['is_root']));
    }

    /**
     * Returns the variable's name.
     *
     * @return string
     */
    public function getVariableName(): string
    {
        return $this->variableName;
    }

    /**
     * Returns the variable's type or null if unknown.
     *
     * @return Type|null
     */
    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->is_root;
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
