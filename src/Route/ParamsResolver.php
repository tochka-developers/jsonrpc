<?php

namespace Tochka\JsonRpc\Route;

use Illuminate\Support\Reflector;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Object_;
use Tochka\JsonRpc\Annotations\ApiMapRequestToObject;
use Tochka\JsonRpc\Annotations\Sometimes;
use Tochka\JsonRpc\Contracts\ApiAnnotationInterface;
use Tochka\JsonRpc\Contracts\ApiAnnotationParameterInterface;
use Tochka\JsonRpc\Contracts\CasterRegistryInterface;
use Tochka\JsonRpc\Contracts\CustomCasterInterface;
use Tochka\JsonRpc\Contracts\DocBlockFactoryInterface;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Facades\JsonRpcCasterRegistry;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Support\DocBlockTypeEnum;
use Tochka\JsonRpc\Support\JsonRpcDocBlock;

class ParamsResolver implements ParamsResolverInterface
{
    /** @var array<string, ParameterObject> */
    private array $classes = [];
    private DocBlockFactoryInterface $docBlockFactory;
    private CasterRegistryInterface $casterRegistry;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(DocBlockFactoryInterface $docBlockFactory, CasterRegistryInterface $casterRegistry)
    {
        $this->docBlockFactory = $docBlockFactory;
        $this->casterRegistry = $casterRegistry;
    }

    /**
     * @return array<string, Parameter>
     * @throws \ReflectionException
     */
    public function resolveParameters(\ReflectionMethod $reflectionMethod): array
    {
        $docBlock = $this->docBlockFactory->make($reflectionMethod);

        if ($docBlock !== null) {
            $mapRequestToObjectAnnotation = $docBlock->firstAnnotation(ApiMapRequestToObject::class);

            if ($mapRequestToObjectAnnotation !== null) {
                $parameterName = $mapRequestToObjectAnnotation->parameterName;

                return $this->mapRequestToObject($reflectionMethod, $parameterName);
            }
        }

        return $this->mapRequestToMethodParameters($reflectionMethod);
    }

    /**
     * @throws \ReflectionException
     */
    public function resolveResult(\ReflectionMethod $reflectionMethod): Parameter
    {
        $reflectionType = $reflectionMethod->getReturnType();

        $docBlock = $this->docBlockFactory->make($reflectionMethod);

        $parameter = $this->getParameterTypeFromReflection('', $reflectionType, $docBlock, DocBlockTypeEnum::RETURN());
        $parameter->required = true;
        $parameter->description = $this->getParameterDescriptionFromPhpDoc('', $docBlock, DocBlockTypeEnum::RETURN());

        return $parameter;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getParameterObject(string $className): ?ParameterObject
    {
        return $this->classes[$className] ?? null;
    }

    /**
     * @return array<string, Parameter>
     * @throws \ReflectionException
     */
    private function mapRequestToObject(\ReflectionMethod $reflectionMethod, string $parameterName): array
    {
        $parameters = [];
        $reflectionParameters = $reflectionMethod->getParameters();

        foreach ($reflectionParameters as $reflectionParameter) {
            $parameter = new Parameter($reflectionParameter->getName(), ParameterTypeEnum::TYPE_OBJECT());

            /** @var class-string|null $type */
            $type = Reflector::getParameterClassName($reflectionParameter);

            if ($type !== null) {
                $parameter->className = $this->fullyQualifiedClassName($type);

                if ($reflectionParameter->getName() === $parameterName) {
                    $parameter->castFullRequest = true;

                    $this->resolveClass($parameter->className);
                } else {
                    $parameter->castFromDI = true;
                }
            }

            $parameter->annotations = $this->getAnnotations($reflectionParameter);

            $parameter->required = !$reflectionParameter->isOptional();
            $parameter->nullable = $reflectionParameter->allowsNull();
            if ($reflectionParameter->isDefaultValueAvailable()) {
                $parameter->defaultValue = $reflectionParameter->getDefaultValue();
                $parameter->hasDefaultValue = true;
            }

            $parameters[$parameter->name] = $parameter;
        }

        return $parameters;
    }

    /**
     * @return array<string, Parameter>
     * @throws \ReflectionException
     */
    private function mapRequestToMethodParameters(\ReflectionMethod $reflectionMethod): array
    {
        $parameters = [];
        $reflectionParameters = $reflectionMethod->getParameters();

        foreach ($reflectionParameters as $reflectionParameter) {
            $parameterName = $reflectionParameter->getName();
            $parameterType = $reflectionParameter->getType();

            $docBlock = $this->docBlockFactory->make($reflectionMethod);

            $parameter = $this->getParameterTypeFromReflection(
                $parameterName,
                $parameterType,
                $docBlock,
                DocBlockTypeEnum::METHOD()
            );

            $parameter->required = !$reflectionParameter->isOptional();
            if ($reflectionParameter->isDefaultValueAvailable()) {
                $parameter->defaultValue = $reflectionParameter->getDefaultValue();
                $parameter->hasDefaultValue = true;
            }

            $parameter->description = $this->getParameterDescriptionFromPhpDoc(
                $parameterName,
                $docBlock,
                DocBlockTypeEnum::METHOD()
            );

            $parameter->annotations = array_merge(
                $this->getAnnotations($reflectionParameter),
                $this->getAnnotationsForParameter($reflectionMethod, $parameterName)
            );

            $parameters[$parameterName] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param class-string $className
     * @throws \ReflectionException
     */
    private function resolveClass(string $className): void
    {
        $className = $this->fullyQualifiedClassName($className);

        if (array_key_exists($className, $this->classes)) {
            return;
        }

        $parameterObject = $this->getParameterObjectForClass($className);
        $this->classes[$className] = $parameterObject;
    }

    /**
     * @param class-string $className
     * @return ParameterObject
     * @throws \ReflectionException
     */
    private function getParameterObjectForClass(string $className): ParameterObject
    {
        $className = $this->fullyQualifiedClassName($className);

        $parameterObject = new ParameterObject($className);

        if (in_array(CustomCasterInterface::class, class_implements($className), true)) {
            $parameterObject->customCastByCaster = $className;
            return $parameterObject;
        }

        $caster = $this->casterRegistry->getCasterForClass($className);
        if ($caster !== null) {
            $parameterObject->customCastByCaster = $caster;
            return $parameterObject;
        }

        $properties = [];

        $reflectionClass = new \ReflectionClass($className);
        $instance = $reflectionClass->newInstanceWithoutConstructor();

        $parameterObject->annotations = $this->getAnnotations($reflectionClass);

        $reflectionProperties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $propertyType = $reflectionProperty->getType();

            $docBlock = $this->docBlockFactory->make($reflectionProperty);

            $property = $this->getParameterTypeFromReflection(
                $propertyName,
                $propertyType,
                $docBlock,
                DocBlockTypeEnum::PROPERTY()
            );

            $property->annotations = $this->getAnnotations($reflectionProperty);

            if ($reflectionProperty->isInitialized($instance)) {
                $property->required = false;
                $property->defaultValue = $reflectionProperty->getValue($instance);
                $property->hasDefaultValue = true;
            } else {
                $property->required = true;

                foreach ($property->annotations as $annotation) {
                    if ($annotation instanceof Sometimes) {
                        $property->required = false;
                        break;
                    }
                }
            }

            $property->description = $this->getParameterDescriptionFromPhpDoc(
                $propertyName,
                $docBlock,
                DocBlockTypeEnum::PROPERTY()
            );

            $properties[$propertyName] = $property;
        }

        $parameterObject->properties = $properties;

        return $parameterObject;
    }

    /**
     * @throws \ReflectionException
     */
    private function getParameterTypeFromReflection(
        string $parameterName,
        ?\ReflectionType $reflectionType,
        ?JsonRpcDocBlock $docBlock,
        DocBlockTypeEnum $docBlockType
    ): Parameter {
        if (!$reflectionType instanceof \ReflectionNamedType) {
            $parameter = new Parameter($parameterName, ParameterTypeEnum::TYPE_MIXED());
            $parameter->nullable = true;
        } else {
            $type = ParameterTypeEnum::fromReflectionType($reflectionType);
            $parameter = new Parameter($parameterName, $type);
            $parameter->nullable = $reflectionType->allowsNull();

            if (!$reflectionType->isBuiltin() && $type->is(ParameterTypeEnum::TYPE_OBJECT())) {
                $parameter->className = $this->fullyQualifiedClassName($reflectionType->getName());
                $this->resolveClass($parameter->className);
            }
            if ($type->is(ParameterTypeEnum::TYPE_ARRAY())) {
                $arrayParam = null;
                if ($docBlock !== null) {
                    $arrayParam = $this->getDocBlockTypeForParameter(
                        $parameterName,
                        $docBlock,
                        $docBlockType
                    );
                }

                if ($arrayParam === null) {
                    $arrayParam = new Parameter('', ParameterTypeEnum::TYPE_MIXED());
                }

                $arrayParam->name = $parameterName;
                $parameter->parametersInArray = $arrayParam;
            }
        }

        return $parameter;
    }

    /**
     * @throws \ReflectionException
     */
    private function getDocBlockTypeForParameter(
        string $parameterName,
        JsonRpcDocBlock $docBlock,
        DocBlockTypeEnum $docBlockType
    ): ?Parameter {
        $paramTag = $this->getParamTagFromDocBlock($docBlock, $docBlockType, $parameterName);

        if (!$paramTag instanceof DocBlock\Tags\TagWithType) {
            return null;
        }

        $paramType = $paramTag->getType();

        if (!$paramType instanceof Array_) {
            return null;
        }

        return $this->getTypeFromDocBlockTag($paramType->getValueType());
    }

    /**
     * @throws \ReflectionException
     */
    private function getTypeFromDocBlockTag(?Type $tagType): ?Parameter
    {
        $parameter = null;

        if ($tagType !== null) {
            $type = ParameterTypeEnum::fromDocBlockType($tagType);
            $parameter = new Parameter('', $type);

            if ($tagType instanceof Object_) {
                /** @var class-string $className */
                $className = $tagType->getFqsen();
                $parameter->className = $this->fullyQualifiedClassName($className);
                $this->resolveClass($parameter->className);
            }

            if ($tagType instanceof Array_) {
                $parameter->parametersInArray = $this->getTypeFromDocBlockTag($tagType->getValueType());
            }
        }

        return $parameter;
    }

    /**
     * @param \Reflector $reflector
     * @return array<ApiAnnotationInterface>
     */
    private function getAnnotations(\Reflector $reflector): array
    {
        $docBlock = $this->docBlockFactory->make($reflector);
        if ($docBlock === null) {
            return [];
        }

        return $docBlock->getAnnotations(ApiAnnotationInterface::class);
    }

    /**
     * @param \Reflector $reflector
     * @param string $parameterName
     * @return array<ApiAnnotationParameterInterface>
     */
    private function getAnnotationsForParameter(\Reflector $reflector, string $parameterName): array
    {
        $docBlock = $this->docBlockFactory->make($reflector);
        if ($docBlock === null) {
            return [];
        }

        return $docBlock->getAnnotations(
            ApiAnnotationParameterInterface::class,
            fn (ApiAnnotationParameterInterface $annotation) => $annotation->getParameterName() === $parameterName
        );
    }

    private function getParameterDescriptionFromPhpDoc(
        string $parameterName,
        ?JsonRpcDocBlock $docBlock,
        DocBlockTypeEnum $docBlockType
    ): ?string {
        if ($docBlock === null) {
            return null;
        }

        $paramTag = $this->getParamTagFromDocBlock($docBlock, $docBlockType, $parameterName);
        if ($paramTag instanceof BaseTag) {
            return $paramTag->getDescription()?->getBodyTemplate();
        }

        return null;
    }

    private function getParamTagFromDocBlock(
        JsonRpcDocBlock $docBlock,
        DocBlockTypeEnum $docBlockType,
        string $parameterName
    ): ?DocBlock\Tag {
        return match ($docBlockType->getValue()) {
            DocBlockTypeEnum::METHOD => $docBlock->firstTag(
                Param::class,
                fn (Param $tag) => $tag->getVariableName() === $parameterName
            ),
            DocBlockTypeEnum::PROPERTY => $docBlock->firstTag(Var_::class),
            DocBlockTypeEnum::RETURN => $docBlock->firstTag(Return_::class),
            default => null,
        };
    }

    /**
     * @param class-string $className
     * @return class-string
     */
    private function fullyQualifiedClassName(string $className): string
    {
        /** @var class-string */
        return '\\' . trim($className, '\\');
    }
}
