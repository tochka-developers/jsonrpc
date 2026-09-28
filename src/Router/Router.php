<?php

namespace Tochka\JsonRpc\Router;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Attributes\ApiMethod;
use Tochka\JsonRpc\Exceptions\JsonPrcRouterException;
use Tochka\JsonRpc\Router\Cache\FileCacheBlob;
use Tochka\JsonRpc\Router\Cache\RouterCacheContract;
use Tochka\JsonRpc\Support\ServerConfig;

class Router
{
    protected array|null $routes = null;
    
    protected string $serverName;
    protected string $namespace;
    protected string $controllerSuffix;
    protected string $methodDelimiter;
    protected CacheInterface $cache;
    
    public function __construct(ServerConfig $config, ?RouterCacheContract $cache = null)
    {
        $this->serverName = $config->serverName;
        $this->namespace = $config->namespace;
        $this->methodDelimiter = $config->methodDelimiter;
        $this->controllerSuffix = $config->controllerSuffix;
        $this->cache = $cache ?? new FileCacheBlob();
    }
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function getRoute(string $method): Route|null
    {
        return $this->loadRoutes()[$method] ?? null;
    }
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function getAll(): array
    {
        return $this->loadRoutes();
    }
    
    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function cacheRoutes(): void
    {
        $routes = $this->parseRoutes();
        $this->cache->set($this->serverName, $routes);
    }
    
    /**
     * @throws InvalidArgumentException
     */
    public function clearRoutesCache(): void
    {
        $this->cache->delete($this->serverName);
    }
    
    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     */
    protected function loadRoutes(): array
    {
        if ($this->routes !== null) {
            return $this->routes;
        }
        $cacheRoutes = $this->cache->get($this->serverName);
        if ($cacheRoutes !== null) {
            $this->routes = $cacheRoutes;
            return $this->routes;
        }
        $this->routes = $this->parseRoutes();
        
        return $this->routes;
    }
    
    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    protected function parseRoutes(): array
    {
        $routes = [];
        $finder = new ControllerFinder();
        $map = $finder->find($this->namespace, $this->controllerSuffix);
        foreach ($map as $class) {
            $reflectionClass = new \ReflectionClass($class);
            $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if ($method->isStatic()) {
                    continue;
                }
                $apiMethodAttribute = $method->getAttributes(ApiMethod::class)[0] ?? null;
                if (!$apiMethodAttribute) {
                    continue;
                }
                $methodName = $this->getMethodName($reflectionClass, $method, $apiMethodAttribute);
                if (array_key_exists($methodName, $routes)) {
                    throw new JsonPrcRouterException('route with name ' . $methodName . ' already exists.');
                }
                $routeParser = new RouteParser(new Route($methodName, $reflectionClass->getName(), $method->getName()));
                $route = $routeParser->fillRoute($method);;
                $routes[$route->name] = $route;
            }
        }
        
        return $routes;
    }
    
    protected function getMethodName(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $method,
        \ReflectionAttribute $attribute
    ): string {
        // try get from attribute
        $args = $attribute->getArguments();
        $methodName = $args[0] ?? $args['name'] ?? null;
        if ($methodName) {
            return $methodName;
        }
        // get from struct
        $shortName = $reflectionClass->getShortName();
        $prefix = lcfirst(str_replace($this->controllerSuffix, '', $shortName));
        
        return $prefix . $this->methodDelimiter . $method->getName();
    }
}
