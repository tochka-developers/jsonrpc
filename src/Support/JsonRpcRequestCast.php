<?php

namespace Tochka\JsonRpc\Support;

use Doctrine\Common\Annotations\Reader;
use Illuminate\Support\Arr;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\AbstractList;
use phpDocumentor\Reflection\Types\AggregatedType;
use phpDocumentor\Reflection\Types\ContextFactory;
use Tochka\JsonRpc\Annotations\CastWith;
use Tochka\JsonRpc\Contracts\DTOCasterInterface;
use Tochka\JsonRpc\Contracts\GlobalPropertyCasterInterface;
use Tochka\JsonRpc\Contracts\JsonRpcCastRequestInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterError;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParametersException;

class JsonRpcRequestCast implements DTOCasterInterface
{
    private const TYPE_NULL = 'NULL';
    private const TYPE_BOOLEAN = 'boolean';
    private const TYPE_INTEGER = 'integer';
    private const TYPE_DOUBLE = 'double';
    private const TYPE_STRING = 'string';
    private const TYPE_ARRAY = 'array';
    private const TYPE_OBJECT = 'object';
    private const TYPE_RESOURCE = 'resource';

    private const REFLECTION_TYPE_MAP = [
        'bool'   => self::TYPE_BOOLEAN,
        'int'    => self::TYPE_INTEGER,
        'float'  => self::TYPE_DOUBLE,
        'string' => self::TYPE_STRING,
        'array'  => self::TYPE_ARRAY,
        'object' => self::TYPE_OBJECT,
    ];

    private const BUILTIN_TYPES = [
        self::TYPE_BOOLEAN,
        self::TYPE_INTEGER,
        self::TYPE_DOUBLE,
        self::TYPE_STRING,
        self::TYPE_ARRAY,
        self::TYPE_OBJECT,
        self::TYPE_RESOURCE,
    ];

    private Reader $annotationReader;
    /** @var array<GlobalPropertyCasterInterface> */
    private array $casters = [];

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function addCaster(GlobalPropertyCasterInterface $caster): void
    {
        $this->casters[] = $caster;
    }

    /**
     * @throws \ReflectionException
     * @throws JsonRpcInvalidParametersException
     */
    public function cast(string $className, object $params, string $parentFieldName = '')
    {
        $instance = new $className();

        if ($instance instanceof JsonRpcCastRequestInterface) {
            $instance->cast($params, $parentFieldName);

            return $instance;
        }

        $reflectionClass = new \ReflectionClass($instance);
        $properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);

        $errors = [];

        foreach ($properties as $property) {
            $field = $property->getName();
            $fullFieldName = implode('.', array_filter([$parentFieldName, $field]));

            $required = !$property->isInitialized($instance);
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $nullable = $type->allowsNull();
                $types = [$this->getCanonicalTypeName($type->getName())];
            } elseif ($type instanceof \ReflectionUnionType) {
                $nullable = $type->allowsNull();
                $types = array_map(
                    function (\ReflectionNamedType $type) {
                        return self::getCanonicalTypeName($type->getName());
                    },
                    $type->getTypes()
                );
            } else {
                $nullable = false;
                $types = [];
            }

            if (!property_exists($params, $field)) {
                if ($required) {
                    $errors[] = new JsonRpcInvalidParameterError(
                        'The field is required, but not present',
                        $fullFieldName
                    );
                }

                continue;
            }

            if (!$nullable && $params->$field === null) {
                $errors[] = new JsonRpcInvalidParameterError('The field is cannot be null', $fullFieldName);
                continue;
            }

            try {
                $instance->$field = $this->getValueByType($types, $params->$field, $property, $fullFieldName);
            } catch (JsonRpcInvalidParametersException $e) {
                /** @var JsonRpcInvalidParameterError[] $errors */
                $exceptionErrors = $e->getErrors();
                foreach ($exceptionErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        if (!empty($errors)) {
            throw new JsonRpcInvalidParametersException($errors);
        }

        return $instance;
    }

    private function getCanonicalTypeName(string $reflectionTypeName): string
    {
        return array_key_exists($reflectionTypeName, self::REFLECTION_TYPE_MAP)
            ? self::REFLECTION_TYPE_MAP[$reflectionTypeName]
            : $reflectionTypeName;
    }

    /**
     * @throws JsonRpcInvalidParametersException
     * @throws \ReflectionException
     */
    protected function getValueByType(array $expectedTypes, $value, \ReflectionProperty $property, string $fieldName)
    {
        /** @var CastWith $customCast */
        $customCast = $this->annotationReader->getPropertyAnnotation($property, CastWith::class);
        if ($customCast !== null) {
            return $customCast->cast($expectedTypes, $value, $property, $fieldName);
        }

        if (empty($expectedTypes)) {
            return $value;
        }

        foreach ($expectedTypes as $type) {
            foreach ($this->casters as $caster) {
                if ($caster->canCast($type, $value, $property, $fieldName)) {
                    return $caster->cast($type, $value, $property, $fieldName);
                }
            }
        }

        $actualType = gettype($value);

        switch ($actualType) {
            case self::TYPE_OBJECT:
                return $this->getValueForObject($actualType, $expectedTypes, $value, $property, $fieldName);
            case self::TYPE_ARRAY:
                return $this->getValueForArray($actualType, $expectedTypes, $value, $property, $fieldName);
            case self::TYPE_INTEGER:
            case self::TYPE_BOOLEAN:
            case self::TYPE_DOUBLE:
            case self::TYPE_STRING:
            case self::TYPE_NULL:
                return $this->getValueForBuiltIn($actualType, $expectedTypes, $value, $fieldName);
            default:
                throw new JsonRpcInvalidParameterException('Unknown field type', $fieldName);
        }
    }

    /**
     * @throws JsonRpcInvalidParametersException
     * @throws \ReflectionException
     */
    protected function getValueForArray(
        string $actualType,
        array $expectedTypes,
        $value,
        \ReflectionProperty $property,
        string $fieldName
    ): array {
        if (!in_array(self::TYPE_ARRAY, $expectedTypes, true)) {
            throw new JsonRpcInvalidParameterException(
                sprintf(
                    'Field type is incorrect. Actual: [%s], Expected: [%s]',
                    $actualType,
                    implode(',', $expectedTypes)
                ),
                $fieldName
            );
        }

        $result = [];
        $types = [];

        // вычитываем тип данных для корректного каста из PhpDoc
        $docFactory = DocBlockFactory::createInstance();
        $phpDocContext = (new ContextFactory())->createFromReflector($property);
        $docBlock = $docFactory->create($property, $phpDocContext);

        /** @var array<Var_> $docBlockVars */
        $docBlockVars = $docBlock->getTagsByName('var');
        foreach ($docBlockVars as $docBlockVar) {
            $type = $docBlockVar->getType();

            if ($type instanceof AggregatedType) {
                /** @var Type $item */
                foreach ($type as $item) {
                    if ($item instanceof AbstractList) {
                        $types[] = $this->getCanonicalTypeName($item->getValueType());
                    }
                }
            } else {
                $types = [$this->getCanonicalTypeName($type)];
            }
        }

        $errors = [];

        foreach ($value as $i => $item) {
            try {
                $result[] = $this->getValueByType($types, $item, $property, $fieldName . '[' . $i . ']');
            } catch (JsonRpcInvalidParametersException $e) {
                foreach ($e->getErrors() as $error) {
                    $errors[] = $error;
                }
            }
        }

        if (!empty($errors)) {
            throw new JsonRpcInvalidParametersException($errors);
        }

        return $result;
    }

    /**
     * @throws \ReflectionException
     * @throws JsonRpcInvalidParametersException
     */
    protected function getValueForObject(
        string $actualType,
        array $expectedTypes,
        $value,
        \ReflectionProperty $property,
        string $fieldName
    ): object {
        $builtinObject = false;

        $setTypes = array_filter(
            $expectedTypes,
            function ($item) use (&$builtinObject) {
                if ($item === self::TYPE_OBJECT) {
                    $builtinObject = true;
                }

                return !\in_array($item, self::BUILTIN_TYPES, true);
            }
        );

        if (empty($setTypes)) {
            if (!$builtinObject) {
                throw new JsonRpcInvalidParameterException(
                    sprintf('Field type is incorrect. Actual: [%s], Expected: [%s]', $actualType, self::TYPE_OBJECT),
                    $fieldName
                );
            }

            return (object)$value;
        }

        $setType = Arr::first($setTypes);

        return $this->cast($setType, $value, $fieldName);
    }

    /**
     * @throws JsonRpcInvalidParameterException
     */
    protected function getValueForBuiltIn(string $actualType, array $expectedTypes, $value, string $fieldName)
    {
        // если среди всех типов нет ни одного из базовых, значит привести к нему не выйдет - выводим ошибку валидации
        $intersectTypes = array_intersect(
            [
                self::TYPE_INTEGER,
                self::TYPE_BOOLEAN,
                self::TYPE_DOUBLE,
                self::TYPE_STRING,
            ],
            $expectedTypes
        );

        if (empty($intersectTypes)) {
            throw new JsonRpcInvalidParameterException(
                sprintf(
                    'Field type is incorrect. Actual: [%s], Expected: [%s]',
                    $actualType,
                    implode(',', $expectedTypes)
                ),
                $fieldName
            );
        }

        // если среди необходимых типов есть актуальный тип - то вернем значение как есть
        if (\in_array($actualType, $intersectTypes, true)) {
            return $value;
        }

        $setType = Arr::first($intersectTypes);
        switch ($setType) {
            case self::TYPE_INTEGER:
                return (int)$value;
            case self::TYPE_STRING:
                return (string)$value;
            case self::TYPE_BOOLEAN:
                return (bool)$value;
            case self::TYPE_DOUBLE:
                return (float)$value;
            default:
                return $value;
        }
    }
}
