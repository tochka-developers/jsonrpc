<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Support\JsonRpcDocBlock;

/**
 * @method static JsonRpcDocBlock|null make(\Reflector $reflector)
 * @method static JsonRpcDocBlock|null makeForClass(string $className)
 * @method static JsonRpcDocBlock|null makeForMethod(string $className, string $methodName)
 * @method static JsonRpcDocBlock|null makeForProperty(string $className, string $propertyName)
 * @method static JsonRpcDocBlock|null makeForParameter(string $className, string $methodName, string $parameterName)
 *
 * @see \Tochka\JsonRpc\Support\JsonRpcDocBlockFactory
 */
class JsonRpcDocBlockFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}
