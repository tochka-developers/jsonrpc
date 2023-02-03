<?php

namespace Tochka\JsonRpc\Support;

use Spiral\Attributes\ReaderInterface;
use Tochka\JsonRpc\Contracts\AnnotationReaderInterface;

class AnnotationReader implements AnnotationReaderInterface
{
    private ReaderInterface $reader;

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    public function getClassMetadata(\ReflectionClass $class, string $name = null): iterable
    {
        return $this->reader->getClassMetadata($class, $name);
    }

    public function firstClassMetadata(\ReflectionClass $class, string $name): ?object
    {
        return $this->reader->firstClassMetadata($class, $name);
    }

    public function getFunctionMetadata(\ReflectionFunctionAbstract $function, string $name = null): iterable
    {
        return $this->reader->getFunctionMetadata($function, $name);
    }

    public function firstFunctionMetadata(\ReflectionFunctionAbstract $function, string $name): ?object
    {
        return $this->reader->firstFunctionMetadata($function, $name);
    }

    public function getPropertyMetadata(\ReflectionProperty $property, string $name = null): iterable
    {
        return $this->reader->getPropertyMetadata($property, $name);
    }

    public function firstPropertyMetadata(\ReflectionProperty $property, string $name): ?object
    {
        return $this->reader->firstPropertyMetadata($property, $name);
    }

    public function getConstantMetadata(\ReflectionClassConstant $constant, string $name = null): iterable
    {
        return $this->reader->getConstantMetadata($constant, $name);
    }

    public function firstConstantMetadata(\ReflectionClassConstant $constant, string $name): ?object
    {
        return $this->reader->firstConstantMetadata($constant, $name);
    }

    public function getParameterMetadata(\ReflectionParameter $parameter, string $name = null): iterable
    {
        return $this->reader->getParameterMetadata($parameter, $name);
    }

    public function firstParameterMetadata(\ReflectionParameter $parameter, string $name): ?object
    {
        return $this->reader->firstParameterMetadata($parameter, $name);
    }
}
