<?php

namespace Tochka\JsonRpc\Route;

use Illuminate\Support\Str;
use Tochka\Hydrator\Contracts\MethodDefinitionParserInterface;
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Annotations\ApiIgnoreMethod;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\TypeParser\Contracts\ExtendedReflectionFactoryInterface;
use Tochka\TypeParser\Contracts\ExtendedReflectionInterface;
use Tochka\TypeParser\Enums\MethodModifierEnum;
use Tochka\TypeParser\Reflectors\ExtendedMethodReflection;

class RouteAggregator implements RouteAggregatorInterface
{
    /** @var array<string, ServerConfig> */
    private array $serverConfigs = [];

    public function __construct(
        private readonly ControllerFinder $controllerFinder,
        private readonly ExtendedReflectionFactoryInterface $extendedReflectionFactory,
        private readonly MethodDefinitionParserInterface $methodDefinitionParser,
    ) {
    }

    public function addServer(string $serverName, ServerConfig $serverConfig): void
    {
        $this->serverConfigs[$serverName] = $serverConfig;
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function getRoutes(): array
    {
        $routes = [];

        foreach ($this->serverConfigs as $serverName => $_) {
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
            $classReflection = $this->extendedReflectionFactory->makeForClass($controller);

            if ($this->isIgnored($classReflection)) {
                continue;
            }

            $methods = $classReflection->getMethods(MethodModifierEnum::IS_PUBLIC);

            foreach ($methods as $reflectionMethod) {
                if ($reflectionMethod->getReflection()->getDeclaringClass()->getName() !== $controller) {
                    continue;
                }

                if ($this->isIgnored($reflectionMethod) || $this->isMethodIgnored($reflectionMethod)) {
                    continue;
                }

                $controllerName = $this->getControllerNameByClass(
                    $classReflection->getName(),
                    $config->controllerSuffix
                );

                switch ($config->dynamicEndpoint) {
                    case ServerConfig::DYNAMIC_ENDPOINT_CONTROLLER_NAMESPACE:
                        $group = $this->diffControllerNamespace($classReflection->getName(), $config->namespace);
                        $action = null;
                        $methodName = $controllerName . $config->methodDelimiter . $reflectionMethod->getName();
                        break;
                    case ServerConfig::DYNAMIC_ENDPOINT_FULL_CONTROLLER_NAME:
                        $group = $this->diffControllerNamespace($classReflection->getName(), $config->namespace);
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

                $route = new JsonRpcRoute(
                    $serverName,
                    $methodName,
                    $group,
                    $action,
                    $this->methodDefinitionParser->getDefinitionFromReflection($reflectionMethod)
                );

                $routes[$route->getRouteName()] = $route;
            }
        }

        return $routes;
    }

    /**
     * Возвращает метку, нужно ли игнорировать текущий контроллер или метод в нем при описании документации
     */
    private function isIgnored(ExtendedReflectionInterface $reflection): bool
    {
        return $reflection->getAttributes()->has(ApiIgnore::class);
    }

    private function isMethodIgnored(ExtendedMethodReflection $reflection): bool
    {
        return !$reflection
                ->getAttributes()
                ->type(ApiIgnoreMethod::class)
                ->filter(fn (ApiIgnoreMethod $item) => $item->name === $reflection->getName())
                ->empty();
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
