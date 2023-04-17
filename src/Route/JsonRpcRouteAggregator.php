<?php

namespace Tochka\JsonRpc\Route;

use Illuminate\Support\Str;
use Spiral\Attributes\ReaderInterface;
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Annotations\ApiIgnoreMethod;
use Tochka\JsonRpc\Facades\JsonRpcParamsResolver;
use Tochka\JsonRpc\Support\ServerConfig;

class JsonRpcRouteAggregator
{
    private ControllerFinder $controllerFinder;
    private ReaderInterface $annotationReader;
    /** @var array<ServerConfig> */
    private array $serverConfigs = [];
    
    public function __construct(ControllerFinder $controllerFinder, ReaderInterface $annotationReader)
    {
        $this->controllerFinder = $controllerFinder;
        $this->annotationReader = $annotationReader;
    }
    
    public function addServer(string $serverName, ServerConfig $serverConfig): void
    {
        $this->serverConfigs[$serverName] = $serverConfig;
    }
    
    public function getServers(): array
    {
        return array_keys($this->serverConfigs);
    }
    
    public function getServerConfig(string $serverName): ?ServerConfig
    {
        return $this->serverConfigs[$serverName] ?? null;
    }
    
    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function getRoutes(): array
    {
        $routes = [];
        
        foreach ($this->serverConfigs as $serverName => $serverConfig) {
            $routes[] = $this->getRoutesForServer($serverName);
        }
        
        return array_merge(...$routes);
    }
    
    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function getRoutesForServer(string $serverName): array
    {
        if (!array_key_exists($serverName, $this->serverConfigs)) {
            return [];
        }
        
        $config = $this->serverConfigs[$serverName];
        $routes = [];
        // пройтись по всем контроллерам и методам и отрезолвить их
        $controllers = $this->controllerFinder->find($config->namespace, $config->controllerSuffix);
        
        foreach ($controllers as $controller) {
            $reflectionController = new \ReflectionClass($controller);
            if ($this->ignoreThis($reflectionController)) {
                continue;
            }
            $ignoredMethods = $this->getIgnoredMethodsFromController($reflectionController);
            
            $reflectionMethods = $reflectionController->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            foreach ($reflectionMethods as $reflectionMethod) {
                if ($reflectionMethod->getDeclaringClass()->getName() !== $controller) {
                    continue;
                }
                
                if (
                    $this->ignoreThis($reflectionMethod)
                    || in_array($reflectionMethod->getName(), $ignoredMethods, true)
                ) {
                    continue;
                }
                
                $controllerName = $this->getControllerNameByClass(
                    $reflectionController->getName(),
                    $config->controllerSuffix
                );
                
                switch ($config->dynamicEndpoint) {
                    case ServerConfig::DYNAMIC_ENDPOINT_CONTROLLER_NAMESPACE:
                        $group = $this->diffControllerNamespace($reflectionController->getName(), $config->namespace);
                        $action = null;
                        $methodName = $controllerName . $config->methodDelimiter . $reflectionMethod->getName();
                        break;
                    case ServerConfig::DYNAMIC_ENDPOINT_FULL_CONTROLLER_NAME:
                        $group = $this->diffControllerNamespace($reflectionController->getName(), $config->namespace);
                        $action = $controllerName;
                        $methodName = $reflectionMethod->getName();
                        break;
                    case ServerConfig::DYNAMIC_ENDPOINT_NONE:
                    default:
                        $group = null;
                        $action = null;
                        $methodName = $controllerName . $config->methodDelimiter . $reflectionMethod->getName();
                        break;
                }
                
                $route = new JsonRpcRoute($serverName, $methodName, $group, $action);
                $route->controllerClass = $reflectionController->getName();
                $route->controllerMethod = $reflectionMethod->getName();
                $route->parameters = JsonRpcParamsResolver::resolveParameters($reflectionMethod);
                $route->result = JsonRpcParamsResolver::resolveResult($reflectionMethod);
                
                $routes[$route->getRouteName()] = $route;
            }
        }
        
        return $routes;
    }
    
    /**
     * Возвращает список методов, которые не нужно описывать в документации
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getIgnoredMethodsFromController(\ReflectionClass $reflectionClass): array
    {
        $ignoredMethods = [];
        $classAnnotations = $this->annotationReader->getClassMetadata($reflectionClass);
        
        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof ApiIgnoreMethod && $classAnnotation->name !== null) {
                $ignoredMethods[] = $classAnnotation->name;
            }
        }
        
        return $ignoredMethods;
    }
    
    /**
     * Возвращает метку, нужно ли игнорировать текущий контроллер или метод в нем при описании документации
     *
     * @param \Reflector $reflection
     *
     * @return bool
     */
    private function ignoreThis(\Reflector $reflection): bool
    {
        if ($reflection instanceof \ReflectionClass) {
            $ignoreAnnotation = $this->annotationReader->firstClassMetadata($reflection, ApiIgnore::class);
            if (!empty($ignoreAnnotation)) {
                return true;
            }
        }
        
        if ($reflection instanceof \ReflectionMethod) {
            $ignoreAnnotation = $this->annotationReader->firstFunctionMetadata($reflection, ApiIgnore::class);
            
            if (!empty($ignoreAnnotation)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getControllerNameByClass(string $className, string $controllerSuffix): string
    {
        return Str::camel(
            Str::replaceLast($controllerSuffix, '', class_basename($className))
        );
    }
    
    private function diffControllerNamespace(string $className, string $namespace): string
    {
        $defaultNamespace = explode('\\', trim($namespace, '\\'));
        $classNamespace = explode('\\', trim($className, '\\'));
        
        $resultNamespace = [];
        
        for ($i = 0, $iMax = count($classNamespace); $i < $iMax; $i++) {
            if (isset($defaultNamespace[$i]) && $classNamespace[$i] === $defaultNamespace[$i]) {
                continue;
            }
            
            if ($i + 1 === $iMax) {
                continue;
            }
            
            $resultNamespace[] = Str::camel($classNamespace[$i]);
        }
        
        return implode('\\', $resultNamespace);
    }
}
