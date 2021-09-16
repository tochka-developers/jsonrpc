<?php

namespace Tochka\JsonRpc\Route;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;

class CacheParamsResolver implements ParamsResolverInterface
{
    private ParamsResolverInterface $paramsResolver;
    private CacheInterface $cache;
    
    public function __construct(ParamsResolverInterface $paramsResolver, CacheInterface $cache)
    {
        $this->paramsResolver = $paramsResolver;
        $this->cache = $cache;
    }
    
    public function resolveParameters(\ReflectionMethod $reflectionMethod): array
    {
        return $this->paramsResolver->resolveParameters($reflectionMethod);
    }
    
    public function resolveResult(\ReflectionMethod $reflectionMethod): Parameter
    {
        return $this->paramsResolver->resolveResult($reflectionMethod);
    }
    
    /**
     * @throws InvalidArgumentException
     */
    public function getClasses(): array
    {
        $classes = $this->cache->get('classes', null);
        return $classes ?? $this->paramsResolver->getClasses();
    }
    
    /**
     * @throws InvalidArgumentException
     */
    public function getParameterObject(string $className): ?ParameterObject
    {
        $classes = $this->cache->get('classes', []);
        
        return $classes[$className] ?? $this->paramsResolver->getParameterObject($className);
    }
}
