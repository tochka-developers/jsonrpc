<?php

namespace Tochka\JsonRpc\DocBlock;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\StaticMethod;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use Webmozart\Assert\Assert;

/**
 * Reflection class for the {@}apiEnum tag in a Docblock.
 */
class ApiEnum extends BaseTag implements StaticMethod
{
    use VariableValueTrait;

    protected const REGEXP = /** @lang text */
        '/(\{(?<typeName>[a-z\._0-9\[\]]+)\}[ ]+)((?<value>[a-z0-9\.\-]+|\"[^\"]+\")[ \n]+)?(?<description>.*)/is';
    protected const TAG_NAME = 'apiEnum';

    /** @var string */
    protected $typeName = '';

    /** @var mixed */
    protected $value;

    /**
     * @param string      $typeName
     * @param mixed       $value
     * @param Description $description
     */
    public function __construct($typeName, $value, Description $description = null)
    {
        Assert::string($typeName);

        $this->name = self::TAG_NAME;
        $this->typeName = $typeName;
        $this->value = $value;
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

        $description = null;

        if (null !== $descriptionFactory) {
            $descriptionStr = isset($parts['description']) ? trim($parts['description']) : '';
            $description = $descriptionFactory->create($descriptionStr, $context);
        }

        /** @var static $param */
        return new static($parts['typeName'], self::getRealValue($parts['value']), $description);
    }

    public function getTypeName()
    {
        return $this->typeName;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return '';
    }
}
