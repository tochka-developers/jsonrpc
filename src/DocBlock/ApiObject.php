<?php

namespace Tochka\JsonRpc\DocBlock;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use Webmozart\Assert\Assert;

/**
 * Reflection class for the {@}apiObject tag in a Docblock.
 */
class ApiObject extends ApiParam
{
    protected const REGEXP_OBJECT = /** @lang text */
        '/(\{(?<objectName>[a-z\._0-9\[\]]+)\}[ ]+)(?<apiParam>.*)/is';
    protected const TAG_NAME = 'apiObject';

    protected $objectName;

    public function __construct(string $variableName, Type $type = null, Description $description = null)
    {
        parent::__construct($variableName, $type, $description);

        $this->name = self::TAG_NAME;
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

        preg_match(self::REGEXP_OBJECT, $body, $parts);

        /** @var static $param */
        $param = parent::create($parts['apiParam'], $typeResolver, $descriptionFactory, $context);
        $param->setObjectName($parts['objectName']);

        return $param;
    }

    public function setObjectName($value): void
    {
        $this->objectName = $value;
    }

    public function getObjectName()
    {
        return $this->objectName;
    }
}
