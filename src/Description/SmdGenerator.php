<?php

namespace Tochka\JsonRpc\Description;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use Tochka\JsonRpc\DocBlock\ApiEnum;
use Tochka\JsonRpc\DocBlock\ApiObject;
use Tochka\JsonRpc\DocBlock\ApiParam;
use Tochka\JsonRpc\DocBlock\ApiReturn;
use Tochka\JsonRpc\DocBlock\Types\Date;
use Tochka\JsonRpc\DocBlock\Types\Enum;
use Tochka\JsonRpc\DocBlock\Types\Object_;
use Tochka\JsonRpc\JsonRpcServer;
use Tochka\JsonRpc\Middleware\AccessControlListMiddleware;
use Tochka\JsonRpc\Middleware\AssociateParamsMiddleware;
use Tochka\JsonRpcSmd\SmdDescription;
use Tochka\JsonRpcSmd\SmdEnumObject;
use Tochka\JsonRpcSmd\SmdEnumValue;
use Tochka\JsonRpcSmd\SmdParameter;
use Tochka\JsonRpcSmd\SmdReturn;
use Tochka\JsonRpcSmd\SmdService;
use Tochka\JsonRpcSmd\SmdSimpleObject;

/**
 * Генератор SMD-схемы для JsonRpc-сервера
 *
 * @package Tochka\JsonRpc
 */
class SmdGenerator
{
    protected const API_GROUP_NAME = 'apiGroupName';
    protected const API_IGNORE_METHOD = 'apiIgnoreMethod';
    protected const API_METHOD_NAME = 'apiName';
    protected const API_METHOD_DESCRIPTION = 'apiDescription';
    protected const API_METHOD_NOTE = 'apiNote';
    protected const API_METHOD_WARNING = 'apiWarning';
    protected const API_METHOD_DEFAULT_PARAMS = 'param';
    protected const API_METHOD_PARAMS = 'apiParam';
    protected const API_METHOD_REQUEST_EXAMPLE = 'apiRequestExample';
    protected const API_METHOD_RESPONSE_EXAMPLE = 'apiResponseExample';
    protected const API_METHOD_RETURN = 'return';
    protected const API_METHOD_RETURN_PARAM = 'apiReturn';
    protected const API_METHOD_TAG = 'apiTag';
    protected const API_ENUM = 'apiEnum';
    protected const API_OBJECT = 'apiObject';
    protected const API_METHOD_DEPRECATED = 'deprecated';

    protected $server;
    protected $acl = false;

    protected $objects = [];

    /** @var DocBlockFactory */
    protected $docFactory;
    protected $additionalTags = [
        self::API_METHOD_RETURN_PARAM => ApiReturn::class,
        self::API_METHOD_PARAMS       => ApiParam::class,
        self::API_ENUM                => ApiEnum::class,
        self::API_OBJECT              => ApiObject::class,
    ];

    public function __construct(JsonRpcServer $server)
    {
        $this->server = $server;
        $this->docFactory = DocBlockFactory::createInstance($this->additionalTags);
    }

    /**
     * @return SmdDescription
     * @throws \ReflectionException
     */
    public function get(): SmdDescription
    {
        $smd = new SmdDescription();

        $smd->target = $this->server->uri;
        $smd->description = $this->server->description;

        if ($this->server->auth) {
            $smd->additionalHeaders = [
                Config::get('jsonrpc.accessHeaderName') => /** @lang text */
                    '<AuthToken>',
            ];
        }

        $smd->namedParameters = \in_array(AssociateParamsMiddleware::class, $this->server->middleware, true);
        $smd->acl = $this->acl = \in_array(AccessControlListMiddleware::class, $this->server->middleware, true);

        $smd->services = $this->getServicesInfo();

        if (!empty($this->objects)) {
            $smd->objects = $this->objects;
        }

        return $smd;
    }

    /**
     * @return SmdService[]
     * @throws \ReflectionException
     */
    protected function getServicesInfo(): array
    {
        $controllers = $this->getControllers($this->server->namespace);

        $services = [];

        foreach ($controllers as $controller) {

            // получаем PHPDoc блока
            $reflection = new \ReflectionClass($controller);
            $docs = $reflection->getDocComment();

            $ignoreMethods = [];
            $group = $this->getShortNameForController($controller);
            $groupName = null;

            if (!empty($docs)) {
                // парсим блок
                $docBlock = $this->docFactory->create($docs);

                // получаем имя для группы методов
                $groupName = $this->getGroupName($docBlock);

                if ($docBlock->hasTag(self::API_IGNORE_METHOD)) {
                    $ignoreMethods = array_map(static function ($value) {
                        return (string) $value;
                    }, $docBlock->getTagsByName(self::API_IGNORE_METHOD));
                }

                /** @var ApiEnum[] $tags */
                $tags = $docBlock->getTagsByName(self::API_ENUM);
                foreach ($tags as $tag) {
                    $this->objects = $this->getDocForEnum($tag, $this->objects);
                }

                /** @var ApiObject[] $tags */
                $tags = $docBlock->getTagsByName(self::API_OBJECT);
                foreach ($tags as $tag) {
                    $this->objects = $this->getDocForObject($tag, $this->objects);
                }
            }

            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->name !== $controller) {
                    continue;
                }
                if (\in_array($method->getName(), $ignoreMethods, true)) {
                    continue;
                }

                $service = $this->generateDocForMethod($method);
                // получаем имя для группы методов
                $service->group = $group;
                if ($groupName !== null) {
                    $service->groupName = $groupName;
                }

                if ($this->acl) {
                    $service->acl = $this->server->acl[$service->name] ?? [];
                }

                if (!empty($this->server->endpoint)) {
                    $namespace = $this->getShortNameForNamespace($reflection->getNamespaceName());
                    $controllerName = $this->getShortNameForController($reflection->getName());

                    if (!empty($namespace)) {
                        $service->endpoint = $namespace . '/' . $controllerName;
                    } else {
                        $service->endpoint = $controllerName;
                    }
                }

                if (!empty($service->endpoint)) {
                    $services[$service->endpoint . '/' . $service->name] = $service;
                } else {
                    $services[$service->name] = $service;
                }
            }
        }

        return $services;
    }

    /**
     * @param DocBlock $docBlock
     *
     * @return string
     */
    protected function getGroupName($docBlock): string
    {
        if ($docBlock->hasTag(self::API_GROUP_NAME)) {
            $tags = $docBlock->getTagsByName(self::API_GROUP_NAME);

            return (string) $tags[0];
        }

        return $docBlock->getSummary();
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return SmdService
     * @throws \ReflectionException
     */
    protected function generateDocForMethod($method): SmdService
    {
        $result = new SmdService();

        $docs = $method->getDocComment();

        if (!$docs) {
            $result->name = $this->getMethodName($method);
            $result->parameters = $this->getMethodParameters($method);
            $result->return = $this->getMethodReturn($method);
        } else {
            $docBlock = $this->docFactory->create($docs);

            $result->name = $this->getMethodName($method, $docBlock);
            $result->description = $this->getMethodDescription($docBlock);

            if ($docBlock->hasTag(self::API_METHOD_DEPRECATED)) {
                $result->deprecated = true;
            }

            if ($docBlock->hasTag(self::API_METHOD_NOTE)) {
                $result->note = (string) $docBlock->getTagsByName(self::API_METHOD_NOTE)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_WARNING)) {
                $result->warning = (string) $docBlock->getTagsByName(self::API_METHOD_WARNING)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_REQUEST_EXAMPLE)) {
                $result->requestExample = (string) $docBlock->getTagsByName(self::API_METHOD_REQUEST_EXAMPLE)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_RESPONSE_EXAMPLE)) {
                $result->responseExample = (string) $docBlock->getTagsByName(self::API_METHOD_RESPONSE_EXAMPLE)[0];
            }

            $objects = [];
            /** @var ApiEnum[] $tags */
            $tags = $docBlock->getTagsByName(self::API_ENUM);
            foreach ($tags as $tag) {
                $objects = $this->getDocForEnum($tag, $objects);
            }

            /** @var ApiObject[] $tags */
            $tags = $docBlock->getTagsByName(self::API_OBJECT);
            foreach ($tags as $tag) {
                $objects = $this->getDocForObject($tag, $objects);
            }
            if (!empty($objects)) {
                $result->objects = $objects;
            }

            $result->parameters = $this->getMethodParameters($method, $docBlock);
            $result->return = $this->getMethodReturn($method, $docBlock);

            /** @var ApiReturn[] $returnParams */
            $returnParams = $docBlock->getTagsByName(self::API_METHOD_RETURN_PARAM);

            $return = [];
            foreach ($returnParams as $param) {
                $return = $this->getExtendedReturns($param, $return);
            }

            if (!empty($return)) {
                $result->returnParameters = $return;
            }

            $tags = array_map(static function ($value) {
                return (string) $value;
            }, $docBlock->getTagsByName(self::API_METHOD_TAG));

            if (!empty($tags)) {
                $result->tags = $tags;
            }
        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getShortNameForController($name): string
    {
        return Str::camel(Str::replaceLast($this->server->postfix, '', class_basename($name)));
    }

    /**
     * @param string $namespace
     *
     * @return string
     */
    protected function getShortNameForNamespace($namespace): string
    {
        return Str::camel(trim(Str::replaceLast(trim($this->server->namespace, '\\'), '', trim($namespace . '\\')),
            '\\'));
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock          $docBlock
     *
     * @return string
     */
    protected function getMethodName($method, $docBlock = null): string
    {
        if ($docBlock !== null && $docBlock->hasTag(self::API_METHOD_NAME)) {
            $tags = $docBlock->getTagsByName(self::API_METHOD_NAME);

            return (string) $tags[0];
        }

        $controllerName = $method->getDeclaringClass();

        if (!empty($this->server->endpoint)) {
            return $method->getName();
        }

        return $this->getShortNameForController($controllerName->getName()) . '_' . $method->getName();
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock          $docBlock
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function getMethodParameters($method, $docBlock = null): array
    {
        $result = [];

        /** @var \ReflectionParameter $param */
        foreach ($method->getParameters() as $param) {
            $parameter = new SmdParameter();
            $parameter->name = $param->getName();
            if (PHP_VERSION_ID > 70000) {
                $parameter->types = [(string) $param->getType()];
            }
            $parameter->optional = $param->isOptional();
            if ($parameter->optional) {
                $parameter->default = $param->getDefaultValue();
            }

            $result[$param->getName()] = $parameter;
        }

        if ($docBlock === null) {
            return array_values($result);
        }

        $params = $docBlock->getTagsByName(self::API_METHOD_DEFAULT_PARAMS);

        /** @var DocBlock\Tags\Param $param */
        foreach ($params as $param) {
            $name = (string) $param->getVariableName();

            $parameter = $result[$name] ?? new SmdParameter();
            $parameter->name = $name;

            if (!empty((string) $param->getDescription())) {
                $parameter->description = (string) $param->getDescription();
            }
            if (!empty((string) $param->getType())) {
                $parameter->types = explode('|', (string) $param->getType());
            }

            $result[$name] = $parameter;
        }

        /** @var ApiParam[] $params */
        $params = $docBlock->getTagsByName(self::API_METHOD_PARAMS);

        foreach ($params as $param) {
            $result = $this->getExtendedParameters($param, $result);
        }

        return array_values($result);
    }

    /**
     * @param ApiParam $docBlock
     * @param array    $current
     *
     * @return array
     */
    protected function getExtendedParameters($docBlock, array $current = []): array
    {
        $name = $docBlock->getVariableName();

        $nameParts = explode('.', $name);
        $variableName = array_shift($nameParts);

        /** @var SmdParameter $parameter */
        if (isset($current[$variableName])) {
            $parameter = $current[$variableName];
        } else {
            $parameter = new SmdParameter();
            $parameter->name = $variableName;
            $current[$variableName] = $parameter;
        }

        while ($key = array_shift($nameParts)) {
            $isArray = false;
            $variableName = $key;
            if (substr($variableName, -2) === '[]') {
                $variableName = substr($variableName, 0, -2);
                $isArray = true;
            }

            $parameter->types = ['object'];
            $parameters = $parameter->parameters;

            if (!isset($parameters[$variableName])) {
                $parameters[$variableName] = new SmdParameter();
            }

            $parameter->parameters = $parameters;
            $parameter = $parameters[$variableName];

            if ($isArray) {
                $parameter->array = true;
            }
        }

        $parameter->name = $variableName;

        $parameter = $this->checkParamType($parameter, $docBlock->getType());

        $parameter->optional = $docBlock->isOptional();

        if (!empty((string) $docBlock->getDescription())) {
            $parameter->description = (string) $docBlock->getDescription();
        }

        if ($docBlock->hasDefault()) {
            $parameter->default = $docBlock->getDefaultValue();
        }
        if ($docBlock->hasExample()) {
            $parameter->example = $docBlock->getExampleValue();
        }

        return $current;
    }

    /**
     * @param ApiReturn $docBlock
     * @param array     $current
     *
     * @return array
     */
    protected function getExtendedReturns($docBlock, array $current = []): array
    {
        $name = $docBlock->getVariableName();

        $nameParts = explode('.', $name);
        $variableName = array_shift($nameParts);

        /** @var SmdParameter $parameter */
        if (isset($current[$variableName])) {
            $parameter = $current[$variableName];
        } else {
            $parameter = new SmdParameter();
            $parameter->name = $variableName;
            $current[$variableName] = $parameter;
        }

        while ($key = array_shift($nameParts)) {
            $isArray = false;
            $variableName = $key;
            if (substr($variableName, -2) === '[]') {
                $variableName = substr($variableName, 0, -2);
                $isArray = true;
            }

            $parameter->types = ['object'];
            $parameters = $parameter->parameters;

            if (!isset($parameters[$variableName])) {
                $parameters[$variableName] = new SmdParameter();
            }

            $parameter->parameters = $parameters;
            $parameter = $parameters[$variableName];

            if ($isArray) {
                $parameter->array = true;
            }
        }

        $parameter->name = $variableName;

        $parameter = $this->checkParamType($parameter, $docBlock->getType());

        if (!empty((string) $docBlock->getDescription())) {
            $parameter->description = (string) $docBlock->getDescription();
        }

        if ($docBlock->isRoot()) {
            $parameter->is_root = true;
        }

        return $current;
    }

    /**
     * @param SmdParameter $parameter
     * @param Type         $type
     *
     * @return mixed
     */
    protected function checkParamType(SmdParameter $parameter, $type)
    {
        switch (true) {
            case $type instanceof Date:
                $parameter->typeFormat = $type->getFormat();
                $parameter->typeAdditional = (string) $type;

                if (empty($parameter->types)) {
                    $parameter->types = ['string'];
                }
                break;

            case $type instanceof Enum:
                if ($type->hasVariants()) {
                    $parameter->typeVariants = $type->getVariants();
                    $parameter->typeAdditional = (string) $type;
                } else {
                    $parameter->typeAdditional = $type->getVariantsType();
                }

                if (empty($parameter->types)) {
                    $parameter->types = [$type->getRealType()];
                }

                break;

            case $type instanceof Object_:
                $parameter->types = ['object'];
                if ($type->getClassName() !== null) {
                    $parameter->typeAdditional = $type->getClassName();
                }
                break;

            case $type instanceof Array_:
                $parameter->array = true;
                $parameter = $this->checkParamType($parameter, $type->getValueType());
                break;

            default:
                $parameter->types = [(string) $type];
        }

        return $parameter;
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock          $docBlock
     *
     * @return SmdReturn
     */
    protected function getMethodReturn($method, $docBlock = null): SmdReturn
    {
        $result = new SmdReturn();
        $result->types = ['mixed'];

        if (PHP_VERSION_ID > 70000) {
            $result->types = [$method->getReturnType()];
        }

        if ($docBlock !== null && $docBlock->hasTag(self::API_METHOD_RETURN)) {
            /** @var DocBlock\Tags\Return_ $return */
            $return = $docBlock->getTagsByName(self::API_METHOD_RETURN)[0];
            if (!empty((string) $return->getType())) {
                $result->types = explode('|', (string) $return->getType());
            }
            if (!empty((string) $return->getDescription())) {
                $result->description = (string) $return->getDescription();
            }
        }

        return $result;
    }

    /**
     * @param DocBlock $docBlock
     *
     * @return string
     */
    protected function getMethodDescription($docBlock): string
    {
        if ($docBlock->hasTag(self::API_METHOD_DESCRIPTION)) {
            $tags = $docBlock->getTagsByName(self::API_METHOD_DESCRIPTION);

            return (string) $tags[0];
        }

        return $docBlock->getSummary();
    }

    /**
     * @param ApiEnum $docBlock
     * @param array   $objects
     *
     * @return array
     */
    protected function getDocForEnum($docBlock, array $objects = []): array
    {
        if (!isset($objects[$docBlock->getTypeName()])) {
            $object = new SmdEnumObject();
            $object->name = $docBlock->getTypeName();
            $object->type = 'int';
            $objects[$docBlock->getTypeName()] = $object;
        } else {
            $object = $objects[$docBlock->getTypeName()];
        }

        $value = $docBlock->getValue();

        switch (true) {
            case \is_int($value):
                break;
            case \is_float($value):
                if ($object->type !== 'string') {
                    $object->type = 'float';
                }
                break;
            default:
                $object->type = 'string';
        }

        $values = $object->values;

        $values[] = SmdEnumValue::fromArray([
            'value'       => $value,
            'description' => (string) $docBlock->getDescription(),
        ]);

        $object->values = $values;

        return $objects;
    }

    /**
     * @param ApiObject $docBlock
     * @param array     $objects
     *
     * @return array
     */
    protected function getDocForObject($docBlock, array $objects = []): array
    {
        $objectName = $docBlock->getObjectName();

        if (!isset($objects[$objectName])) {
            $object = new SmdSimpleObject();
            $object->name = $objectName;
            $objects[$objectName] = $object;
        } else {
            $object = $objects[$objectName];
        }

        $object->parameters = $this->getExtendedParameters($docBlock, $object->parameters);

        return $objects;
    }

    private function getControllers($namespace): array
    {
        $namespace = trim($namespace, '\\');

        $files = scandir($this->getNamespaceDirectory($namespace), SCANDIR_SORT_ASCENDING);

        $controllers = [];
        $controllerList = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $directory = $this->getNamespaceDirectory($namespace . '\\' . $file);

            if (is_dir($directory)) {
                $controllerList[] = $this->getControllers($namespace . '\\' . $file);
            } else {
                $className = $namespace . '\\' . str_replace('.php', '', $file);
                if (class_exists($className)) {
                    $controllers[] = $className;
                }
            }
        }

        $controllers = array_merge($controllers, ...$controllerList);

        return $controllers;
    }

    private function getDefinedNamespaces(): array
    {
        $composerJsonPath = app()->basePath() . DIRECTORY_SEPARATOR . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath), false);

        return (array) $composerConfig->autoload->{'psr-4'};
    }

    /**
     * @param $namespace
     *
     * @return bool|string
     */
    private function getNamespaceDirectory($namespace)
    {
        $composerNamespaces = $this->getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';

            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                $path = app()->basePath() . DIRECTORY_SEPARATOR . $composerNamespaces[$possibleNamespace] . implode('/',
                        array_reverse($undefinedNamespaceFragments));

                return realpath($path);
            }

            $undefinedNamespaceFragments[] = array_pop($namespaceFragments);
        }

        return false;
    }
}