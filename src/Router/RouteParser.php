<?php

namespace Tochka\JsonRpc\Router;

use Tochka\JsonRpc\Attributes\ApiDI;
use Tochka\JsonRpc\Attributes\ApiParams;
use Tochka\JsonRpc\Attributes\ApiValidation;
use Tochka\JsonRpc\Exceptions\JsonPrcRouterException;
use Tochka\JsonRpc\Traits\WithValidation;

class RouteParser
{
    protected Route $route;
    protected RouteValidation $validation;
    protected array $path = [];
    
    public function __construct(Route $route)
    {
        $this->route = $route;
        $this->validation = new RouteValidation();
    }
    
    protected function pathAdd(string $chunk): void
    {
        $this->path[] = $chunk;
    }
    
    protected function pathRm(): void
    {
        array_pop($this->path);
    }
    
    protected function pathGet(string $propName): string
    {
        return implode('.', [...$this->path, $propName]);
    }
    
    /**
     * @throws \Exception
     */
    public function fillRoute(\ReflectionMethod $method): Route
    {
        $this->parseMethodParameters($method);
        $this->route->setValidation($this->validation);
        
        return $this->route;
    }
    
    /**
     * @return RouteParam[]
     * @throws \ReflectionException
     * @throws JsonPrcRouterException
     */
    protected function parseClassProperties(\ReflectionClass $class): array
    {
        /** @var RouteParam[] $result */
        $result = [];
        $this->addValidationFromTrait($class);
        
        
        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            
            $apiValidation = $prop->getAttributes(ApiValidation::class)[0] ?? null;
            $types = $prop->getType();
            
            if ($types instanceof \ReflectionIntersectionType) {
                throw new JsonPrcRouterException('Not supported ReflectionIntersectionType for property');
            }
            
            $this->addValidationFromAttribute($prop->getName(), $apiValidation);
            
            // тип не определён считаем его mixed и даём пихать что угодно
            if (!$types) {
                $result[$prop->getName()] = $this->paramTypeMixed($prop->getName(), $prop->hasDefaultValue());
                continue;
            }
            
            if ($types instanceof \ReflectionUnionType) {
                $result[$prop->getName()] = $this->paramTypeUnion($prop->getName(), $types, $prop->hasDefaultValue());
                continue;
            }
            
            if ($types instanceof \ReflectionNamedType) {
                $result[$prop->getName()] = $this->paramTypeSingular(
                    $prop->getName(),
                    $types,
                    $prop->hasDefaultValue()
                );
            }
        }
        
        return $result;
    }
    
    /**
     * @throws JsonPrcRouterException
     * @throws \ReflectionException
     */
    protected function parseMethodParameters(\ReflectionMethod $method): void
    {
        foreach ($method->getParameters() as $param) {
            $this->path = [];
            
            $types = $param->getType();
            
            if ($types instanceof \ReflectionIntersectionType) {
                throw new JsonPrcRouterException('Not supported ReflectionIntersectionType for property');
            }
            
            $apiParamsAttr = $param->getAttributes(ApiParams::class);
            if ($apiParamsAttr) {
                $this->route->addParam($this->paramTypeApiParams($param->getName(), $types, $param->isOptional()));
                continue;
            }
            
            $apiDIAttr = $param->getAttributes(ApiDI::class);
            if ($apiDIAttr) {
                $this->route->addParam($this->paramTypeDI($param->getName(), $types, $param->isOptional()));
                continue;
            }
            
            $apiValidation = $param->getAttributes(ApiValidation::class)[0] ?? null;
            $this->addValidationFromAttribute($param->getName(), $apiValidation);
            
            // тип не определён считаем его mixed и даём пихать что угодно
            if (!$types) {
                $this->route->addParam($this->paramTypeMixed($param->getName()));
                continue;
            }
            
            if ($types instanceof \ReflectionUnionType) {
                $this->route->addParam($this->paramTypeUnion($param->getName(), $types));
                continue;
            }
            
            if ($types instanceof \ReflectionNamedType) {
                $this->route->addParam($this->paramTypeSingular($param->getName(), $types, $param->isOptional()));
            }
        }
    }
    
    
    protected function addValidationFromAttribute(string $name, \ReflectionAttribute|null $attribute): void
    {
        if (!$attribute) {
            return;
        }
        $args = $attribute->getArguments();
        /** @var string|array $rules */
        $rules = $args[0] ?? $args['rules'] ?? [];
        if (empty($rules)) {
            return;
        }
        
        $this->validation->rules[$this->pathGet($name)] = $rules;
    }
    
    /**
     *
     * @param \ReflectionClass $reflectionClass
     * @return void
     */
    protected function addValidationFromTrait(\ReflectionClass $reflectionClass): void
    {
        $className = $reflectionClass->getName();
        $uses = class_uses_recursive($className);
        if (\in_array(WithValidation::class, $uses)) {
            /** @var WithValidation $className */
            
            $rules = [];
            foreach ($className::rules() as $name => $rule) {
                $rules[$this->pathGet($name)] = $rule;
            }
            $this->validation->rules = [...$this->validation->rules, ...$rules];
            
            $messages = [];
            foreach ($className::messages() as $name => $message) {
                $messages[$this->pathGet($name)] = $message;
            }
            $this->validation->messages = [...$this->validation->messages, ...$messages];
            
            $attributes = [];
            foreach ($className::attributes() as $name => $attribute) {
                $attributes[$this->pathGet($name)] = $attribute;
            }
            $this->validation->attributes = [...$this->validation->attributes, ...$attributes];
        }
    }
    
    /**
     * @throws JsonPrcRouterException
     */
    protected function paramTypeDI(string $name, \ReflectionType $type, bool $isOptional = false): RouteParam
    {
        if ($type instanceof \ReflectionUnionType) {
            throw new JsonPrcRouterException('Not supported ReflectionUnionType for ApiDI');
        }
        
        if ($isOptional) {
            throw new JsonPrcRouterException('ApiDI can`t be optional');
        }
        
        if ($type->allowsNull()) {
            throw new JsonPrcRouterException('ApiDI can`t be nullable');
        }
        
        /** @var \ReflectionNamedType $type */
        $class = $type->getName();
        if (!class_exists($class) && !interface_exists($class)) {
            throw new JsonPrcRouterException('ApiDI must be valid and existing class or interface');
        }
        
        return new RouteParam(
            name:         $name,
            propType:     PropType::DI,
            allowedTypes: [],
            isNullable:   false,
            className:    $type->getName(),
            isOptional:   false,
        );
    }
    
    /**
     * @throws \Tochka\JsonRpc\Exceptions\JsonPrcRouterException
     * @throws \ReflectionException
     */
    protected function paramTypeApiParams(string $name, \ReflectionType $type, bool $isOptional = false): RouteParam
    {
        if ($type instanceof \ReflectionUnionType) {
            throw new JsonPrcRouterException('Not supported ReflectionUnionType for ApiParams');
        }
        
        if ($isOptional) {
            throw new JsonPrcRouterException('ApiParams can`t be optional');
        }
        
        if ($type->allowsNull()) {
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
        
        $this->addValidationFromTrait(new \ReflectionClass($type->getName()));
        
        return new RouteParam(
            name:         $name,
            propType:     PropType::RequestObject,
            allowedTypes: [],
            isNullable:   false,
            className:    $class,
            isOptional:   false,
            params:       $this->parseClassProperties($reflectionClass),
        );
    }
    
    protected function paramTypeMixed(
        string $name,
        bool $isOptional = false,
    ): RouteParam {
        return new RouteParam(
            name:         $name,
            propType:     PropType::Mixed,
            allowedTypes: [],
            isNullable:   true,
            className:    null,
            isOptional:   $isOptional,
        );
    }
    
    /**
     * @throws JsonPrcRouterException
     */
    protected function paramTypeUnion(
        string $name,
        \ReflectionUnionType $type,
        bool $isOptional = false
    ): RouteParam {
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
            name:         $name,
            propType:     PropType::Primitive,
            allowedTypes: $allowedTypes,
            isNullable:   $type->allowsNull(),
            className:    null,
            isOptional:   $isOptional,
        );
    }
    
    /**
     * @throws \ReflectionException
     * @throws JsonPrcRouterException
     */
    protected function paramTypeSingular(string $name, \ReflectionNamedType $type, bool $isOptional): RouteParam
    {
        // mixed type
        if ($type->getName() === 'mixed') {
            return $this->paramTypeMixed($name, $isOptional);
        }
        
        // primitive type
        if ($type->isBuiltin()) {
            if (\in_array($type->getName(), ['callable', 'iterable'])) {
                throw new JsonPrcRouterException('Not supported this type ' . $type->getName());
            }
            
            return new RouteParam(
                name:         $name,
                propType:     PropType::Primitive,
                allowedTypes: [$type->getName()],
                isNullable:   $type->allowsNull(),
                className:    null,
                isOptional:   $isOptional,
            );
        }
        
        // enum type
        if (enum_exists($type->getName())) {
            $enumReflector = new \ReflectionEnum($type->getName());
            $enumType = $enumReflector->getBackingType()?->getName();
            if (!$enumType) {
                throw new JsonPrcRouterException('Not supported pure enum for property, they cant be created');
            }
            
            return new RouteParam(
                name:         $name,
                propType:     PropType::Enum,
                allowedTypes: [$enumType],
                isNullable:   $type->allowsNull(),
                className:    $type->getName(),
                isOptional:   $isOptional,
            );
        }
        
        $this->pathAdd($name);
        
        $param = new RouteParam(
            name:         $name,
            propType:     PropType::Object,
            allowedTypes: ['object'],
            isNullable:   $type->allowsNull(),
            className:    $type->getName(),
            isOptional:   $isOptional,
            params:       $this->parseClassProperties(new \ReflectionClass($type->getName())),
        );
        
        $this->pathRm();
        
        return $param;
    }
}
