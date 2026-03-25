<?php

namespace Tochka\JsonRpc\Router;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Helpers\ArrayFileCache;
use Tochka\JsonRpc\Support\ServerConfig;

class Router
{
    protected array|null $routes = null;
    
    protected string $serverName;
    protected string $namespace;
    protected string $controllerSuffix;
    protected string $methodDelimiter;
    protected bool $allowParentMethods;
    protected CacheInterface $cache;
    protected RouteParser $routeParser;
    
    public function __construct(ServerConfig $config)
    {
        $this->serverName = $config->serverName;
        $this->namespace = $config->namespace;
        $this->methodDelimiter = $config->methodDelimiter;
        $this->controllerSuffix = $config->controllerSuffix;
        $this->allowParentMethods = $config->allowParentMethods;
        // todo надо сделать по нормальному
        $this->cache = new ArrayFileCache($config->serverName);
        $this->routeParser = new RouteParser();
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
    
    public function clearRoutesCache(): void
    {
        $this->cache->clear();
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
                if ($this->checkIsIgnored($reflectionClass, $method)) {
                    continue;
                }
                $methodName = $this->getMethodNameFromStruct($reflectionClass, $method);
                $route = $this->routeParser->createRoute($reflectionClass, $method, $methodName);
                $routes[$route->name] = $route;
            }
        }
        
        return $routes;
    }
    
    /** Если название метода не определено явно, то собираем его из имени контроллера и метода */
    protected function getMethodNameFromStruct(\ReflectionClass $reflectionClass, \ReflectionMethod $method): string
    {
        $shortName = $reflectionClass->getShortName();
        $prefix = lcfirst(str_replace($this->controllerSuffix, '', $shortName));
        
        return $prefix . $this->methodDelimiter . $method->getName();
    }
    
    // пока проверяем по старому, для обратной совместимости, потом уберём
    protected function checkIsIgnored(\ReflectionClass $class, \ReflectionMethod $method): bool
    {
        $methodName = $method->getName();
        if (str_starts_with($methodName, '__')) {
            return true;
        }
        if (!$this->allowParentMethods && $method->getDeclaringClass()->getName() !== $class->getName()) {
            return true;
        }
        if (str_contains($method->getDocComment(), '@ApiIgnore')) {
            return true;
        }
        if (!$method->isPublic() || $method->isStatic()) {
            return true;
        }
        
        return false;
    }
}
