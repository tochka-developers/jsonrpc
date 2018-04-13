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
 * Reflection class for the {@}param tag in a Docblock.
 */
class ApiParam extends BaseTag implements StaticMethod
{
    use VariableValueTrait;

    const REGEXP = '/((?<require>\*) +)?(((?<type>[a-z\[\]]+)(\=(?<typeFormat>[a-z0-9]+|"[^"]+"|\([^\)]+\)))?) +)(\$(?<variableName>[a-z\._0-9\[\]]+)(=(?<defaultValue>[a-z0-9\.\-]+|\"[^\"]*\"))?[ \n]+)(\((?<exampleValue>[a-z0-9\.\-]+|\"[^\"]+\")\)[ \n]+)?(?<description>.*)?/is';

    /** @var string */
    protected $name = 'apiParam';

    /** @var Type */
    protected $type;

    /** @var string */
    protected $variableName = '';

    /** @var mixed */
    protected $defaultValue = null;

    /** @var bool */
    protected $hasDefault = false;

    /** @var mixed */
    protected $exampleValue = null;

    /** @var bool */
    protected $hasExample = false;

    /** @var bool */
    protected $optional = true;

    /**
     * @param string      $variableName
     * @param Type        $type
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
     * @param                         $body
     * @param TypeResolver|null       $typeResolver
     * @param DescriptionFactory|null $descriptionFactory
     * @param TypeContext|null        $context
     *
     * @return static
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

        $typeStr = isset($parts['type']) ? trim($parts['type']) : 'mixed';
        $typeExtended = isset($parts['typeFormat']) ? $parts['typeFormat'] : null;
        $variableName = isset($parts['variableName']) ? $parts['variableName'] : 'variable';

        $type = CustomTypeResolver::resolve($typeStr, $typeExtended);

        /** @var static $param */
        $param = new static($variableName, $type, $description);

        $param->setOptional(empty($parts['require']));

        if (!empty($parts['defaultValue'])) {
            $param->setDefault(self::getRealValue($parts['defaultValue']));
        }
        if (!empty($parts['exampleValue'])) {
            $param->setExample(self::getRealValue($parts['exampleValue']));
        }

        return $param;
    }

    /**
     * Имя параметра
     * @return string
     */
    public function getVariableName()
    {
        return $this->variableName;
    }

    /**
     * Тип параметра
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Есть ли значение по умолчанию
     * @return bool
     */
    public function hasDefault()
    {
        return $this->hasDefault;
    }

    /**
     * Значение по умолчанию
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Устанавливает значение по умолчанию
     *
     * @param $value
     */
    public function setDefault($value)
    {
        $this->hasDefault = true;
        $this->defaultValue = $value;
    }

    /**
     * Есть ли пример значения
     * @return bool
     */
    public function hasExample()
    {
        return $this->hasExample;
    }

    /**
     * Пример значения
     * @return mixed
     */
    public function getExampleValue()
    {
        return $this->exampleValue;
    }

    /**
     * Устанавливает пример значения
     *
     * @param $value
     */
    public function setExample($value)
    {
        $this->hasExample = true;
        $this->exampleValue = $value;
    }

    /**
     * Является ли параметр необязательным
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * Устанавливает необязательность параметра
     *
     * @param $value
     */
    public function setOptional($value)
    {
        $this->optional = $value;
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
