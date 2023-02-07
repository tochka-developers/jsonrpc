<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Support\JsonRpcDocBlock;

/**
 * @psalm-api
 */
interface DocBlockFactoryInterface
{
    public function make(\Reflector $reflector): ?JsonRpcDocBlock;

    /**
     * @param class-string $className
     * @return JsonRpcDocBlock|null
     */
    public function makeForClass(string $className): ?JsonRpcDocBlock;

    /**
     * @param class-string $className
     * @param string $methodName
     * @return JsonRpcDocBlock|null
     */
    public function makeForMethod(string $className, string $methodName): ?JsonRpcDocBlock;

    /**
     * @param class-string $className
     * @param string $propertyName
     * @return JsonRpcDocBlock|null
     */
    public function makeForProperty(string $className, string $propertyName): ?JsonRpcDocBlock;

    /**
     * @param class-string $className
     * @param string $methodName
     * @param string $parameterName
     * @return JsonRpcDocBlock|null
     */
    public function makeForParameter(string $className, string $methodName, string $parameterName): ?JsonRpcDocBlock;
}
