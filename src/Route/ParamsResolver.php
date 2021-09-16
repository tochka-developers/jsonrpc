<?php

namespace Tochka\JsonRpc\Route;

use Illuminate\Support\Reflector;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Object_;
use Tochka\JsonRpc\Annotations\ApiMapRequestToObject;
use Tochka\JsonRpc\Contracts\ApiAnnotationContract;
use Tochka\JsonRpc\Contracts\CustomCasterInterface;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Facades\JsonRpcRequestCast;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Support\DocBlockTypeEnum;
use Tochka\JsonRpc\Support\JsonRpcDocBlock;

class ParamsResolver implements ParamsResolverInterface
{
    /** @var array<string, ParameterObject> */
    private array $classes = [];
    
    /**
     * @throws \ReflectionException
     */
    public function resolveParameters(\ReflectionMethod $reflectionMethod): array
    {
        $docBlock = JsonRpcDocBlockFactory::make($reflectionMethod);
        
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
        
        $docBlock = JsonRpcDocBlockFactory::make($reflectionMethod);
        
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
     * @throws \ReflectionException
     */
    private function mapRequestToObject(\ReflectionMethod $reflectionMethod, string $parameterName): array
    {
        $parameters = [];
        $reflectionParameters = $reflectionMethod->getParameters();
        
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameter = new Parameter($reflectionParameter->getName(), ParameterTypeEnum::TYPE_OBJECT());
            
            $type = Reflector::getParameterClassName($reflectionParameter);
            
            if ($type) {
                if ($reflectionParameter->getName() === $parameterName) {
                    $parameter->className = $this->fullyQualifiedClassName($type);
                    $parameter->castFullRequest = true;
                    
                    $this->resolveClass($parameter->className);
                } else {
                    $parameter->castFromDI = true;
                    $parameter->className = $this->fullyQualifiedClassName($type);
                }
            }
            
            $parameter->annotations = $this->getAnnotations($reflectionParameter);
            
            $parameter->required = !$reflectionParameter->isOptional();
            $parameter->nullable = $reflectionParameter->allowsNull();
            if ($reflectionParameter->isDefaultValueAvailable()) {
                $parameter->defaultValue = $reflectionParameter->getDefaultValue();
            }
            
            $parameters[] = $parameter;
        }
        
        return $parameters;
    }
    
    /**
     * @throws \ReflectionException
     */
    private function mapRequestToMethodParameters(\ReflectionMethod $reflectionMethod): array
    {
        $parameters = [];
        $reflectionParameters = $reflectionMethod->getParameters();
        
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameterName = $reflectionParameter->getName();
            $parameterType = $reflectionParameter->getType();
            
            $docBlock = JsonRpcDocBlockFactory::make($reflectionMethod);
            
            $parameter = $this->getParameterTypeFromReflection(
                $parameterName,
                $parameterType,
                $docBlock,
                DocBlockTypeEnum::METHOD()
            );
            
            $parameter->required = !$reflectionParameter->isOptional();
            if ($reflectionParameter->isDefaultValueAvailable()) {
                $parameter->defaultValue = $reflectionParameter->getDefaultValue();
            }
            
            $parameter->description = $this->getParameterDescriptionFromPhpDoc(
                $parameterName,
                $docBlock,
                DocBlockTypeEnum::METHOD()
            );
            
            $parameter->annotations = $this->getAnnotations($reflectionParameter);
            
            $parameters[$parameterName] = $parameter;
        }
        return $parameters;
    }
    
    /**
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
        
        $caster = JsonRpcRequestCast::getCasterForClass($className);
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
            
            $docBlock = JsonRpcDocBlockFactory::make($reflectionProperty);
            
            $property = $this->getParameterTypeFromReflection(
                $propertyName,
                $propertyType,
                $docBlock,
                DocBlockTypeEnum::PROPERTY()
            );
            
            if ($reflectionProperty->isInitialized($instance)) {
                $property->required = false;
                $property->defaultValue = $reflectionProperty->getValue($instance);
            } else {
                $property->required = true;
            }
            
            $property->description = $this->getParameterDescriptionFromPhpDoc(
                $propertyName,
                $docBlock,
                DocBlockTypeEnum::PROPERTY()
            );
            
            $property->annotations = $this->getAnnotations($reflectionProperty);
            
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
                $parameter->className = $this->fullyQualifiedClassName($tagType->getFqsen());
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
     * @return array<ApiAnnotationContract>
     */
    private function getAnnotations(\Reflector $reflector): array
    {
        $docBlock = JsonRpcDocBlockFactory::make($reflector);
        if ($docBlock === null) {
            return [];
        }
        
        return $docBlock->getAnnotations(
            null,
            fn($annotation) => $annotation instanceof ApiAnnotationContract
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
        
        if ($paramTag === null) {
            return null;
        }
        
        return $paramTag->getDescription();
    }
    
    private function getParamTagFromDocBlock(
        JsonRpcDocBlock $docBlock,
        DocBlockTypeEnum $docBlockType,
        string $parameterName
    ): ?DocBlock\Tag {
        switch ($docBlockType->value) {
            case DocBlockTypeEnum::METHOD:
                return $docBlock->firstTag(
                    Param::class,
                    fn(Param $tag) => $tag->getVariableName() === $parameterName
                );
            case DocBlockTypeEnum::PROPERTY:
                return $docBlock->firstTag(Var_::class);
            case DocBlockTypeEnum::RETURN:
                return $docBlock->firstTag(Return_::class);
            default:
                return null;
        }
    }
    
    private function fullyQualifiedClassName(string $className): string
    {
        return '\\' . trim($className, '\\');
    }
}
