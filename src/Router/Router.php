<?php

namespace Tochka\JsonRpc\Router;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Attributes\ApiDI;
use Tochka\JsonRpc\Attributes\ApiParams;
use Tochka\JsonRpc\Exceptions\JsonPrcRouterException;
use Tochka\JsonRpc\Helpers\ArrayFileCache;
use Tochka\JsonRpc\Support\ServerConfig;

class Router
{
    protected array|null $routes = null;
    
    protected string $serverName;
    public string $namespace;
    public string $controllerSuffix;
    public string $methodDelimiter;
    public bool $allowParentMethods;
    public CacheInterface $cache;
    
    public function __construct(ServerConfig $config)
    {
        $this->serverName = $config->serverName;
        $this->namespace = $config->namespace;
        $this->methodDelimiter = $config->methodDelimiter;
        $this->controllerSuffix = $config->controllerSuffix;
        $this->allowParentMethods = $config->allowParentMethods;
        // todo надо сделать по нормальному
        $this->cache = new ArrayFileCache($config->serverName);
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
                $route = $this->getMethodParams($reflectionClass, $method);
                $routes[$route->name] = $route;
            }
        }
        
        return $routes;
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
    
    /**
     * @throws \Exception
     */
    protected function getMethodParams(\ReflectionClass $class, \ReflectionMethod $method): Route
    {
        $route = new Route(
            $this->getMethodNameFromStruct($class, $method),
            $class->getName(),
            $method->getName(),
        );
        
        foreach ($method->getParameters() as $param) {
            $types = $param->getType();
            // тип не определён считаем его mixed и даём пихать что угодно
            if (!$types) {
                $route->addParam($this->paramTypeMixed($param));
                continue;
            }
            
            if ($types instanceof \ReflectionIntersectionType) {
                throw new JsonPrcRouterException('Not supported ReflectionIntersectionType for property');
            }
            
            if ($types instanceof \ReflectionUnionType) {
                $route->addParam($this->paramTypeUnion($param, $types));
                continue;
            }
            
            if ($types instanceof \ReflectionNamedType) {
                $route->addParam($this->paramTypeSingular($param, $types));
            }
        }
        
        return $route;
    }
    
    protected function paramTypeMixed(\ReflectionParameter $param): RouteParam
    {
        return new RouteParam(
            name:         $param->getName(),
            propType:     PropType::Mixed,
            allowedTypes: [],
            isNullable:   true,
            className:    null,
            isOptional:   $param->isOptional(),
        );
    }
    
    /**
     * @throws JsonPrcRouterException
     */
    protected function paramTypeUnion(\ReflectionParameter $param, \ReflectionUnionType $type): RouteParam
    {
        $allowedTypes = [];
        $isBuiltinCheck = [];
        foreach ($type->getTypes() as $typeItem) {
            $isBuiltinCheck[] = $typeItem->isBuiltin();
            $allowedTypes[] = $typeItem->getName();
        }
        
        if (!array_all($isBuiltinCheck, fn($v) => $v)) {
            throw new JsonPrcRouterException('Not supported property union type with class types');
        }
        
        return new RouteParam(
            name:         $param->getName(),
            propType:     PropType::Primitive,
            allowedTypes: $allowedTypes,
            isNullable:   $type->allowsNull(),
            className:    null,
            isOptional:   $param->isOptional()
        );
    }
    
    /**
     * @throws \ReflectionException
     * @throws JsonPrcRouterException
     */
    protected function paramTypeSingular(\ReflectionParameter $param, \ReflectionNamedType $type): RouteParam
    {
        // mixed type
        if ($type->getName() === 'mixed') {
            return $this->paramTypeMixed($param);
        }
        
        // primitive type
        if ($type->isBuiltin()) {
            return new RouteParam(
                name:         $param->getName(),
                propType:     PropType::Primitive,
                allowedTypes: [$type->getName()],
                isNullable:   $param->allowsNull(),
                className:    null,
                isOptional:   $param->isOptional(),
            );
        }
        
        // enum type
        if (enum_exists($type->getName())) {
            $enumReflector = new \ReflectionEnum($type->getName());
            $enumType = $enumReflector->getBackingType()?->getName();
            if(!$enumType) {
                throw new JsonPrcRouterException('Not supported pure enum for property, they cant be serialized');
            }
            return new RouteParam(
                name:         $param->getName(),
                propType:     PropType::Enum,
                allowedTypes: [$enumType],
                isNullable:   $param->allowsNull(),
                className:    $type->getName(),
                isOptional:   $param->isOptional(),
            );
        }
        $apiParams = $param->getAttributes(ApiParams::class);
        if ($apiParams) {
            return new RouteParam(
                name:         $param->getName(),
                propType:     PropType::RequestObject,
                allowedTypes: [],
                isNullable:   false,
                className:    $type->getName(),
                isOptional:   $param->isOptional(),
            );
        }
        
        $apiDI = $param->getAttributes(ApiDI::class);
        if ($apiDI) {
            return new RouteParam(
                name:         $param->getName(),
                propType:     PropType::DI,
                allowedTypes: [],
                isNullable:   false,
                className:    $type->getName(),
                isOptional:   $param->isOptional(),
            );
        }
        // structured type
        return new RouteParam(
            name:         $param->getName(),
            propType:     PropType::Object,
            allowedTypes: ['object'],
            isNullable:   $param->allowsNull(),
            className:    $type->getName(),
            isOptional:   $param->isOptional(),
        );
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
    
    /** Если название метода не определено явно, то собираем его из имени контроллера и метода */
    protected function getMethodNameFromStruct(\ReflectionClass $reflectionClass, \ReflectionMethod $method): string
    {
        $shortName = $reflectionClass->getShortName();
        $prefix = lcfirst(str_replace($this->controllerSuffix, '', $shortName));
        
        return $prefix . $this->methodDelimiter . $method->getName();
    }
}
