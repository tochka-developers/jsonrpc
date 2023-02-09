<?php

namespace Tochka\JsonRpc\Route;

use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;

class CacheParamsResolver implements ParamsResolverInterface
{
    private ParamsResolverInterface $paramsResolver;
    private RouteCacheInterface $cache;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(ParamsResolverInterface $paramsResolver, RouteCacheInterface $cache)
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
        /** @var array<string, ParameterObject>|null $classes */
        $classes = $this->cache->get('classes', null);
        return $classes ?? $this->paramsResolver->getClasses();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getParameterObject(string $className): ?ParameterObject
    {
        /** @var array<string, ParameterObject>|null $classes */
        $classes = $this->cache->get('classes', []);

        return $classes[$className] ?? $this->paramsResolver->getParameterObject($className);
    }
}
