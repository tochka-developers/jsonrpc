<?php

namespace Tochka\JsonRpc\Support;

use JetBrains\PhpStorm\Pure;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Spiral\Attributes\ReaderInterface;

class JsonRpcDocBlock
{
    private ?DocBlock $docBlock = null;
    private ReaderInterface $annotationReader;
    private \Reflector $reflector;
    private DocBlockFactory $docBlockFactory;
    
    public function __construct(
        \Reflector $reflector,
        ReaderInterface $annotationReader,
        DocBlockFactory $docBlockFactory
    ) {
        $this->reflector = $reflector;
        $this->annotationReader = $annotationReader;
        $this->docBlockFactory = $docBlockFactory;
        
        if (!method_exists($reflector, 'getDocComment')) {
            return;
        }
        
        $docComment = $reflector->getDocComment();
        if (empty($docComment)) {
            return;
        }
        
        $phpDocContext = (new ContextFactory())->createFromReflector($reflector);
        $this->docBlock = $this->docBlockFactory->create($docComment, $phpDocContext);
    }
    
    public function getSummary(): ?string
    {
        if (
            !empty($this->docBlock)
            && (
                $this->reflector instanceof \ReflectionMethod
                || $this->reflector instanceof \ReflectionClass
            )
        ) {
            return $this->docBlock->getSummary() ?: null;
        }
        
        if (
            $this->reflector instanceof \ReflectionProperty
            || $this->reflector instanceof \ReflectionClassConstant
        ) {
            if (!empty($this->docBlock)) {
                $summary = $this->docBlock->getSummary() ?: null;
                if (!empty($summary)) {
                    return $summary;
                }
            }
            
            $tag = $this->firstTag(DocBlock\Tags\Var_::class);
            if ($tag !== null && $tag->getDescription() !== null) {
                return $tag->getDescription()->getBodyTemplate();
            }
        }
        
        if ($this->reflector instanceof \ReflectionParameter) {
            if (!empty($this->docBlock)) {
                $summary = $this->docBlock->getSummary() ?: null;
                if (!empty($summary)) {
                    return $summary;
                }
            }
            
            $reflectionMethod = $this->reflector->getDeclaringFunction();
            $docComment = $reflectionMethod->getDocComment();
            $phpDocContext = (new ContextFactory())->createFromReflector($reflectionMethod);
            $docBlock = $this->docBlockFactory->create($docComment, $phpDocContext);
            
            $tag = $this->firstTag(
                DocBlock\Tags\Param::class,
                fn(DocBlock\Tags\Param $tag) => $tag->getVariableName() === $this->reflector->getName(),
                $docBlock
            );
            
            if ($tag !== null && $tag->getDescription() !== null) {
                return $tag->getDescription()->getBodyTemplate();
            }
        }
        
        return null;
    }
    
    public function getDescription(): ?string
    {
        if (
            !empty($this->docBlock)
            && (
                $this->reflector instanceof \ReflectionMethod
                || $this->reflector instanceof \ReflectionClass
            )
        ) {
            $description = $this->docBlock->getDescription()->getBodyTemplate() ?: null;
        }
        
        if (empty($this->docBlock)) {
            return null;
        }
        
        $description = $this->docBlock->getDescription()->getBodyTemplate();
        if (!empty($description)) {
            return $description;
        }
        
        $summary = $this->docBlock->getSummary();
        
        if (!empty($summary)) {
            return $summary;
        }
        
        return $this->getSummary();
    }
    
    /**
     * @template T
     * @param class-string<T> $tagClassName
     * @return bool
     */
    public function hasTag(string $tagClassName): bool
    {
        return $this->firstTag($tagClassName) !== null;
    }
    
    /**
     * @template T
     * @param class-string<T>|null $tagClassName
     * @return T|null
     */
    public function firstTag(?string $tagClassName = null, callable $filter = null, ?DocBlock $docBlock = null): ?object
    {
        $docBlock = $docBlock ?: $this->docBlock;
        
        if ($docBlock === null) {
            return null;
        }
        
        $tags = $docBlock->getTags();
        
        foreach ($tags as $tag) {
            if ($tagClassName !== null && !$tag instanceof $tagClassName) {
                continue;
            }
            
            if ($filter !== null && !$filter($tag)) {
                continue;
            }
            
            return $tag;
        }
        
        return null;
    }
    
    /**
     * @template T
     * @param class-string<T>|null $tagClassName
     * @return array<T>
     */
    public function getTags(?string $tagClassName = null, callable $filter = null, ?DocBlock $docBlock = null): array
    {
        $docBlock = $docBlock ?: $this->docBlock;
        
        if ($docBlock === null) {
            return [];
        }
        
        $tags = $docBlock->getTags();
        return array_filter($tags, static function (DocBlock\Tag $tag) use ($tagClassName, $filter) {
            if ($tagClassName !== null && !$tag instanceof $tagClassName) {
                return false;
            }
            
            if ($filter !== null) {
                return $filter($tag);
            }
            
            return true;
        });
    }
    
    /**
     * @template T
     * @param class-string<T> $annotationClassName
     * @return bool
     */
    public function hasAnnotation(string $annotationClassName): bool
    {
        return $this->firstAnnotation($annotationClassName) !== null;
    }
    
    /**
     * @template T
     * @param class-string<T>|null $annotationClassName
     * @return array<T>
     */
    public function getAnnotations(?string $annotationClassName = null, callable $filter = null): array
    {
        $annotations = $this->getAnnotationsByReflector($annotationClassName);
        
        $result = [];
        foreach ($annotations as $annotation) {
            if ($filter !== null && !$filter($annotation)) {
                continue;
            }
            
            $result[] = $annotation;
        }
        
        return $result;
    }
    
    /**
     * @template T
     * @param class-string<T>|null $annotationClassName
     * @return T|null
     */
    public function firstAnnotation(string $annotationClassName, callable $filter = null): ?object
    {
        $annotations = $this->getAnnotationsByReflector($annotationClassName);
        
        foreach ($annotations as $annotation) {
            if ($filter === null || $filter($annotation)) {
                return $annotation;
            }
        }
        
        return null;
    }
    
    /**
     * @template T
     * @param class-string<T>|null $annotationClassName
     * @return array<T>
     */
    private function getAnnotationsByReflector(?string $annotationClassName = null): iterable
    {
        $annotations = [];
        
        if ($this->reflector instanceof \ReflectionClass) {
            $annotations = $this->annotationReader->getClassMetadata($this->reflector, $annotationClassName);
        }
        
        if ($this->reflector instanceof \ReflectionProperty) {
            $annotations = $this->annotationReader->getPropertyMetadata($this->reflector, $annotationClassName);
        }
        
        if ($this->reflector instanceof \ReflectionFunctionAbstract) {
            $annotations = $this->annotationReader->getFunctionMetadata($this->reflector, $annotationClassName);
        }
        
        if ($this->reflector instanceof \ReflectionParameter) {
            $annotations = $this->annotationReader->getParameterMetadata($this->reflector, $annotationClassName);
        }
        
        if ($this->reflector instanceof \ReflectionClassConstant) {
            $annotations = $this->annotationReader->getConstantMetadata($this->reflector, $annotationClassName);
        }
        
        return $annotations;
    }
    
    public function getReflector(): \Reflector
    {
        return $this->reflector;
    }
}
