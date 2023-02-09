<?php

namespace Tochka\JsonRpc\Support;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Tochka\JsonRpc\Contracts\AnnotationReaderInterface;

/**
 * @psalm-type Attributes = object
 * @psalm-api
 */
class JsonRpcDocBlock
{
    private ?DocBlock $docBlock = null;
    private AnnotationReaderInterface $annotationReader;
    private \Reflector $reflector;
    private DocBlockFactory $docBlockFactory;

    public function __construct(
        \Reflector $reflector,
        AnnotationReaderInterface $annotationReader,
        DocBlockFactory $docBlockFactory
    ) {
        $this->reflector = $reflector;
        $this->annotationReader = $annotationReader;
        $this->docBlockFactory = $docBlockFactory;

        if (!method_exists($reflector, 'getDocComment')) {
            return;
        }

        /** @var string|false $docComment */
        $docComment = $reflector->getDocComment();
        if ($docComment === false) {
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

            return $tag?->getDescription()?->getBodyTemplate();
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
            $reflectionParameterName = $this->reflector->getName();

            $tag = $this->firstTag(
                DocBlock\Tags\Param::class,
                fn (DocBlock\Tags\Param $tag) => $tag->getVariableName() === $reflectionParameterName,
                $docBlock
            );

            return $tag?->getDescription()?->getBodyTemplate();
        }

        return null;
    }

    public function getDescription(): ?string
    {
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
     * @template T of Tag
     * @param class-string<T> $tagClassName
     * @return bool
     */
    public function hasTag(string $tagClassName): bool
    {
        return $this->firstTag($tagClassName) !== null;
    }

    /**
     * @template T of Tag
     * @param class-string<T>|null $tagClassName
     * @param callable|null $filter
     * @param DocBlock|null $docBlock
     * @return T|null
     */
    public function firstTag(
        ?string $tagClassName = null,
        callable $filter = null,
        ?DocBlock $docBlock = null
    ): ?Tag {
        $docBlock = $docBlock ?: $this->docBlock;

        if ($docBlock === null) {
            return null;
        }

        $tags = $docBlock->getTags();

        foreach ($tags as $tag) {
            if ($tagClassName !== null && !$tag instanceof $tagClassName) {
                continue;
            }

            /** @var T $tag */

            if ($filter !== null && !$filter($tag)) {
                continue;
            }

            return $tag;
        }

        return null;
    }

    /**
     * @template T of Tag
     * @param class-string<T>|null $tagClassName
     * @param callable(T):bool|null $filter
     * @param DocBlock|null $docBlock
     * @return array<T>
     */
    public function getTags(?string $tagClassName = null, callable $filter = null, ?DocBlock $docBlock = null): array
    {
        $docBlock = $docBlock ?: $this->docBlock;

        if ($docBlock === null) {
            return [];
        }

        /** @var array<T> $tags */
        $tags = $docBlock->getTags();
        return array_filter(
            $tags,
            static function (DocBlock\Tag $tag) use ($tagClassName, $filter) {
                if ($tagClassName !== null && !$tag instanceof $tagClassName) {
                    return false;
                }

                /** @var T $tag */

                if ($filter !== null) {
                    return $filter($tag);
                }

                return true;
            }
        );
    }

    /**
     * @template T of Attributes
     * @param class-string<T> $annotationClassName
     * @return bool
     */
    public function hasAnnotation(string $annotationClassName): bool
    {
        return $this->firstAnnotation($annotationClassName) !== null;
    }

    /**
     * @template T of Attributes
     * @param class-string<T>|null $annotationClassName
     * @param callable(T):bool|null $filter
     * @return array<T>
     */
    public function getAnnotations(?string $annotationClassName = null, callable $filter = null): array
    {
        $annotations = $this->getAnnotationsByReflector();

        $result = [];

        foreach ($annotations as $annotation) {
            if ($annotationClassName !== null && !$annotation instanceof $annotationClassName) {
                continue;
            }

            /** @var T $annotation */
            if ($filter !== null && !$filter($annotation)) {
                continue;
            }

            $result[] = $annotation;
        }

        return $result;
    }

    /**
     * @template T of Attributes
     * @param class-string<T> $annotationClassName
     * @param callable(T):bool|null $filter
     * @return T|null
     */
    public function firstAnnotation(string $annotationClassName, callable $filter = null): ?object
    {
        $annotations = $this->getAnnotationsByReflector();

        foreach ($annotations as $annotation) {
            if (!$annotation instanceof $annotationClassName) {
                continue;
            }

            if ($filter !== null && !$filter($annotation)) {
                continue;
            }

            return $annotation;
        }

        return null;
    }

    /**
     * @return iterable<Attributes>
     */
    private function getAnnotationsByReflector(): iterable
    {
        if ($this->reflector instanceof \ReflectionClass) {
            return $this->annotationReader->getClassMetadata($this->reflector);
        } elseif ($this->reflector instanceof \ReflectionProperty) {
            return $this->annotationReader->getPropertyMetadata($this->reflector);
        } elseif ($this->reflector instanceof \ReflectionFunctionAbstract) {
            return $this->annotationReader->getFunctionMetadata($this->reflector);
        } elseif ($this->reflector instanceof \ReflectionParameter) {
            return $this->annotationReader->getParameterMetadata($this->reflector);
        } elseif ($this->reflector instanceof \ReflectionClassConstant) {
            return $this->annotationReader->getConstantMetadata($this->reflector);
        } else {
            return [];
        }
    }

    public function getReflector(): \Reflector
    {
        return $this->reflector;
    }
}
