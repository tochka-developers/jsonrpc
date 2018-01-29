<?php

namespace Tochka\JsonRpc\DocBlock;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\StaticMethod;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use Webmozart\Assert\Assert;

/**
 * Reflection class for the {@}apiObject tag in a Docblock.
 */
class ApiObject extends ApiParam
{
    const REGEXP_OBJECT = '/(\{(?<objectName>[a-z\._0-9\[\]]+)\}[ ]+)(?<apiParam>.*)/is';

    /** @var string */
    protected $name = 'apiObject';

    protected $objectName;

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

    public function setObjectName($value)
    {
        $this->objectName = $value;
    }

    public function getObjectName()
    {
        return $this->objectName;
    }
}
