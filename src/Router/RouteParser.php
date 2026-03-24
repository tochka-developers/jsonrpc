<?php

namespace Tochka\JsonRpc\Router;

use Tochka\JsonRpc\Attributes\ApiDI;
use Tochka\JsonRpc\Attributes\ApiParams;
use Tochka\JsonRpc\Exceptions\JsonPrcRouterException;

class RouteParser
{
    /**
     * @throws \Exception
     */
    public function createRoute(\ReflectionClass $class, \ReflectionMethod $method, string $name): Route
    {
        $route = new Route(
            $name,
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
            $apiParamsAttr = $param->getAttributes(ApiParams::class);
            if ($apiParamsAttr) {
                $route->addParam($this->paramTypeApiParams($param, $types));
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

    /**
     * @throws \Tochka\JsonRpc\Exceptions\JsonPrcRouterException
     */
    protected function paramTypeApiParams(\ReflectionParameter $param, \ReflectionType $type): RouteParam
    {
        if ($type instanceof \ReflectionUnionType) {
            throw new JsonPrcRouterException('Not supported ReflectionUnionType for ApiParams');
        }

        if ($type instanceof \ReflectionIntersectionType) {
            throw new JsonPrcRouterException('Not supported ReflectionIntersectionType for ApiParams');
        }

        if ($param->isOptional()) {
            throw new JsonPrcRouterException('ApiParams can`t be optional');
        }

        if ($param->allowsNull()) {
            throw new JsonPrcRouterException('ApiParams can`t be nullable');
        }

        /** @var \ReflectionNamedType $type */
        $class = $type->getName();
        if (!class_exists($class)) {
            throw new JsonPrcRouterException('ApiParams must be valid and existing class');
        }

        $reflectionClass = new \ReflectionClass($class);
        if (!$reflectionClass->isInstantiable()) {
            throw new JsonPrcRouterException('ApiParams class must be instantiable');
        }

        return new RouteParam(
            name: $param->getName(),
            propType: PropType::RequestObject,
            allowedTypes: [],
            isNullable: false,
            className: $class,
            isOptional: false,
        );
    }

    protected function paramTypeMixed(\ReflectionParameter $param): RouteParam
    {
        return new RouteParam(
            name: $param->getName(),
            propType: PropType::Mixed,
            allowedTypes: [],
            isNullable: true,
            className: null,
            isOptional: $param->isOptional(),
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
            name: $param->getName(),
            propType: PropType::Primitive,
            allowedTypes: $allowedTypes,
            isNullable: $type->allowsNull(),
            className: null,
            isOptional: $param->isOptional()
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
            if (\in_array($type->getName(), ['callable', 'iterable'])) {
                throw new JsonPrcRouterException('Not supported this type ' . $type->getName());
            }

            return new RouteParam(
                name: $param->getName(),
                propType: PropType::Primitive,
                allowedTypes: [$type->getName()],
                isNullable: $param->allowsNull(),
                className: null,
                isOptional: $param->isOptional(),
            );
        }

        // enum type
        if (enum_exists($type->getName())) {
            $enumReflector = new \ReflectionEnum($type->getName());
            $enumType = $enumReflector->getBackingType()?->getName();
            if (!$enumType) {
                throw new JsonPrcRouterException('Not supported pure enum for property, they cant be serialized');
            }

            return new RouteParam(
                name: $param->getName(),
                propType: PropType::Enum,
                allowedTypes: [$enumType],
                isNullable: $param->allowsNull(),
                className: $type->getName(),
                isOptional: $param->isOptional(),
            );
        }

        $apiDI = $param->getAttributes(ApiDI::class);
        if ($apiDI) {
            return new RouteParam(
                name: $param->getName(),
                propType: PropType::DI,
                allowedTypes: [],
                isNullable: false,
                className: $type->getName(),
                isOptional: false,
            );
        }

        // structured type
        return new RouteParam(
            name: $param->getName(),
            propType: PropType::Object,
            allowedTypes: ['object'],
            isNullable: $param->allowsNull(),
            className: $type->getName(),
            isOptional: $param->isOptional(),
        );
    }
}
