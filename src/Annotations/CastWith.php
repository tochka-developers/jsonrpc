<?php

namespace Tochka\JsonRpc\Annotations;

use Tochka\JsonRpc\Contracts\PropertyCasterInterface;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class CastWith
{
    public string $className;

    public function cast(array $expectedTypes, $value, \ReflectionProperty $property, string $fieldName)
    {
        $caster = new $this->className();
        if (!$caster instanceof PropertyCasterInterface) {
            throw new \RuntimeException(
                sprintf(
                    'A custom caster is used for the class field [%s:%s], which is not an implementation of the [%s]',
                    $property->getDeclaringClass()->getName(),
                    $property->getName(),
                    PropertyCasterInterface::class
                )
            );
        }

        return $caster->cast($expectedTypes, $value, $property, $fieldName);
    }
}
