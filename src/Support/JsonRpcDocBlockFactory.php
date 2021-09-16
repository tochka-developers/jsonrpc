<?php

namespace Tochka\JsonRpc\Support;

use phpDocumentor\Reflection\DocBlockFactory;
use Spiral\Attributes\ReaderInterface;

class JsonRpcDocBlockFactory
{
    /** @var array<string, JsonRpcDocBlock> */
    private array $docBlocks = [];
    private ReaderInterface $annotationReader;
    private DocBlockFactory $docBlockFactory;
    
    public function __construct(ReaderInterface $annotationReader, DocBlockFactory $docBlockFactory)
    {
        $this->annotationReader = $annotationReader;
        $this->docBlockFactory = $docBlockFactory;
    }
    
    public function make(\Reflector $reflector): ?JsonRpcDocBlock
    {
        $callable = static function () use ($reflector) {
            return $reflector;
        };
        
        if ($reflector instanceof \ReflectionClass) {
            return $this->getOrMakeFromReflection(
                $this->getKeyForClass($reflector->getName()),
                $callable
            );
        }
        
        if ($reflector instanceof \ReflectionMethod) {
            return $this->getOrMakeFromReflection(
                $this->getKeyForMethod($reflector->getDeclaringClass()->getName(), $reflector->getName()),
                $callable
            );
        }
        
        if ($reflector instanceof \ReflectionProperty) {
            return $this->getOrMakeFromReflection(
                $this->getKeyForProperty($reflector->getDeclaringClass()->getName(), $reflector->getName()),
                $callable
            );
        }
        
        if ($reflector instanceof \ReflectionFunction) {
            return $this->getOrMakeFromReflection(
                $this->getKeyForFunction($reflector->getName()),
                $callable
            );
        }
        
        if ($reflector instanceof \ReflectionParameter) {
            return $this->getOrMakeFromReflection(
                $this->getKeyForParameter(
                    $reflector->getDeclaringFunction(),
                    $reflector->getName(),
                    $reflector->getDeclaringClass()
                ),
                $callable
            );
        }
        if ($reflector instanceof \ReflectionClassConstant) {
            return $this->getOrMakeFromReflection(
                $this->getKeyForClassConstant($reflector->getDeclaringClass()->getName(), $reflector->getName()),
                $callable
            );
        }
        
        
        return null;
    }
    
    public function makeForClass(string $className): ?JsonRpcDocBlock
    {
        try {
            return $this->getOrMakeFromReflection(
                $this->getKeyForClass($className),
                function () use ($className) {
                    return new \ReflectionClass($className);
                }
            );
        } catch (\ReflectionException $e) {
            return null;
        }
    }
    
    public function makeForMethod(string $className, string $methodName): ?JsonRpcDocBlock
    {
        try {
            return $this->getOrMakeFromReflection(
                $this->getKeyForMethod($className, $methodName),
                function () use ($className, $methodName) {
                    return new \ReflectionMethod($className, $methodName);
                }
            );
        } catch (\ReflectionException $e) {
            return null;
        }
    }
    
    public function makeForProperty(string $className, string $propertyName): ?JsonRpcDocBlock
    {
        try {
            return $this->getOrMakeFromReflection(
                $this->getKeyForProperty($className, $propertyName),
                function () use ($className, $propertyName) {
                    return new \ReflectionProperty($className, $propertyName);
                }
            );
        } catch (\ReflectionException $e) {
            return null;
        }
    }
    
    public function makeForParameter(string $className, string $methodName, string $parameterName): ?JsonRpcDocBlock
    {
        try {
            return $this->getOrMakeFromReflection(
                $this->getKeyForParameter($className, $methodName, $parameterName),
                function () use ($className, $methodName, $parameterName) {
                    return new \ReflectionParameter([$className, $methodName], $parameterName);
                }
            );
        } catch (\ReflectionException $e) {
            return null;
        }
    }
    
    private function getOrMakeFromReflection(string $key, callable $reflectorCallable): ?JsonRpcDocBlock
    {
        if (!array_key_exists($key, $this->docBlocks)) {
            $this->docBlocks[$key] = new JsonRpcDocBlock(
                $reflectorCallable(),
                $this->annotationReader,
                $this->docBlockFactory
            );
        }
        
        return $this->docBlocks[$key];
    }
    
    private function getKeyForClass(string $className): string
    {
        return sprintf('class:%s', $className);
    }
    
    private function getKeyForMethod(string $className, string $methodName): string
    {
        return sprintf('method:%s:%s', $className, $methodName);
    }
    
    private function getKeyForProperty(string $className, string $propertyName): string
    {
        return sprintf('property:%s:%s', $className, $propertyName);
    }
    
    private function getKeyForFunction(string $functionName): string
    {
        return sprintf('function:%s', $functionName);
    }
    
    private function getKeyForParameter(string $methodName, string $parameterName, ?string $className = null): string
    {
        if ($className === null) {
            $className = '$global$';
        }
        return sprintf('parameter:%s:%s:%s', $className, $methodName, $parameterName);
    }
    
    private function getKeyForClassConstant(string $className, string $constantName): string
    {
        return sprintf('classConstant:%s:%s', $className, $constantName);
    }
}
