<?php

namespace Tochka\JsonRpc;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Array_;
use Tochka\JsonRpc\DocBlock\ApiEnum;
use Tochka\JsonRpc\DocBlock\ApiObject;
use Tochka\JsonRpc\DocBlock\ApiParam;
use Tochka\JsonRpc\DocBlock\ApiReturn;
use Tochka\JsonRpc\DocBlock\Types\Date;
use Tochka\JsonRpc\DocBlock\Types\Enum;
use Tochka\JsonRpc\Middleware\AccessControlListMiddleware;
use Tochka\JsonRpc\Middleware\AssociateParamsMiddleware;

/**
 * Генератор SMD-схемы для JsonRpc-сервера
 * @package Tochka\JsonRpc
 */
class SmdGenerator
{
    const API_GROUP_NAME = 'apiGroupName';
    const API_IGNORE_METHOD = 'apiIgnoreMethod';
    const API_METHOD_NAME = 'apiName';
    const API_METHOD_DESCRIPTION = 'apiDescription';
    const API_METHOD_NOTE = 'apiNote';
    const API_METHOD_WARNING = 'apiWarning';
    const API_METHOD_DEFAULT_PARAMS = 'param';
    const API_METHOD_PARAMS = 'apiParam';
    const API_METHOD_REQUEST_EXAMPLE = 'apiRequestExample';
    const API_METHOD_RESPONSE_EXAMPLE = 'apiResponseExample';
    const API_METHOD_RETURN = 'return';
    const API_METHOD_RETURN_PARAM = 'apiReturn';
    const API_METHOD_TAG = 'apiTag';
    const API_ENUM = 'apiEnum';
    const API_OBJECT = 'apiObject';
    const API_METHOD_DEPRECATED = 'deprecated';

    const DEFAULT_STRUCTURE = [
        'transport'   => 'POST',
        'envelope'    => 'JSON-RPC-2.0',
        'SMDVersion'  => '2.0',
        'contentType' => 'application/json',
        'generator'   => 'Tochka/JsonRpc',
    ];

    protected $options = [];
    protected $acl = false;

    protected $enumObjects = [];
    protected $objects = [];

    /** @var DocBlockFactory */
    protected $docFactory;
    protected $additionalTags = [
        self::API_METHOD_RETURN_PARAM => ApiReturn::class,
        self::API_METHOD_PARAMS       => ApiParam::class,
        self::API_ENUM                => ApiEnum::class,
        self::API_OBJECT              => ApiObject::class,
    ];

    public function __construct($options)
    {
        $this->options = $options;
        $this->docFactory = DocBlockFactory::createInstance($this->additionalTags);
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function get()
    {
        $result = self::DEFAULT_STRUCTURE;

        $result['target'] = $this->options['uri'];
        $result['description'] = $this->options['description'];

        if ($this->options['auth']) {
            $result['additionalHeaders'] = [
                config('jsonrpc.accessHeaderName') => '<AuthToken>',
            ];
        }

        $result['namedParameters'] = in_array(AssociateParamsMiddleware::class, $this->options['middleware'], true);
        $result['acl'] = $this->acl = in_array(AccessControlListMiddleware::class, $this->options['middleware'], true);

        $result['services'] = $this->getServicesInfo();

        if (!empty($this->enumObjects)) {
            $result['enumObjects'] = $this->enumObjects;
        }

        if (!empty($this->objects)) {
            $result['objects'] = $this->objects;
        }

        return $result;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    protected function getServicesInfo()
    {
        $controllers = $this->getControllers();

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
                    $ignoreMethods = array_map(function ($value) {
                        return (string)$value;
                    }, $docBlock->getTagsByName(self::API_IGNORE_METHOD));
                }

                /** @var ApiEnum[] $tags */
                $tags = $docBlock->getTagsByName(self::API_ENUM);
                foreach ($tags as $tag) {
                    $this->enumObjects = $this->getDocForEnum($tag, $this->enumObjects);
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
                if (in_array($method->getName(), $ignoreMethods, true)) {
                    continue;
                }

                $service = $this->generateDocForMethod($method);
                // получаем имя для группы методов
                $service['group'] = $group;
                if ($groupName !== null) {
                    $service['groupName'] = $groupName;
                }

                if ($this->acl) {
                    $service['acl'] = isset($this->options['acl'][$service['name']]) ? $this->options['acl'][$service['name']] : [];
                }

                $services[$service['name']] = $service;
            }
        }

        return $services;
    }

    /**
     * @param DocBlock $docBlock
     *
     * @return string
     */
    protected function getGroupName($docBlock)
    {
        if ($docBlock->hasTag(self::API_GROUP_NAME)) {
            $tags = $docBlock->getTagsByName(self::API_GROUP_NAME);

            return (string)$tags[0];
        }

        return $docBlock->getSummary();
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    protected function generateDocForMethod($method)
    {
        $result = [];

        $docs = $method->getDocComment();

        if (!$docs) {
            $result['name'] = $this->getMethodName($method);
            $result['parameters'] = $this->getMethodParameters($method);
            $result['return'] = $this->getMethodReturn($method);
        } else {
            $docBlock = $this->docFactory->create($docs);

            $result['name'] = $this->getMethodName($method, $docBlock);
            $result['description'] = $this->getMethodDescription($docBlock);

            if ($docBlock->hasTag(self::API_METHOD_DEPRECATED)) {
                $result['deprecated'] = true;
            }

            if ($docBlock->hasTag(self::API_METHOD_NOTE)) {
                $result['note'] = (string)$docBlock->getTagsByName(self::API_METHOD_NOTE)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_WARNING)) {
                $result['warning'] = (string)$docBlock->getTagsByName(self::API_METHOD_WARNING)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_REQUEST_EXAMPLE)) {
                $result['requestExample'] = (string)$docBlock->getTagsByName(self::API_METHOD_REQUEST_EXAMPLE)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_RESPONSE_EXAMPLE)) {
                $result['responseExample'] = (string)$docBlock->getTagsByName(self::API_METHOD_RESPONSE_EXAMPLE)[0];
            }

            $enumObjects = [];
            /** @var ApiEnum[] $tags */
            $tags = $docBlock->getTagsByName(self::API_ENUM);
            foreach ($tags as $tag) {
                $enumObjects = $this->getDocForEnum($tag, $enumObjects);
            }
            if (!empty($enumObjects)) {
                $result['enumObjects'] = $enumObjects;
            }

            $objects = [];
            /** @var ApiObject[] $tags */
            $tags = $docBlock->getTagsByName(self::API_OBJECT);
            foreach ($tags as $tag) {
                $objects = $this->getDocForObject($tag, $objects);
            }
            if (!empty($objects)) {
                $result['objects'] = $objects;
            }

            $result['parameters'] = $this->getMethodParameters($method, $docBlock);
            $result['returns'] = $this->getMethodReturn($method, $docBlock);

            /** @var ApiReturn[] $returnParams */
            $returnParams = $docBlock->getTagsByName(self::API_METHOD_RETURN_PARAM);

            $return = [];
            foreach ($returnParams as $param) {
                $return = $this->getExtendedReturns($param, $return);
            }

            if (!empty($return)) {
                $result['returnParameters'] = $return;
            }

            $tags = array_map(function ($value) {
                return (string)$value;
            }, $docBlock->getTagsByName(self::API_METHOD_TAG));

            if (!empty($tags)) {
                $result['tags'] = $tags;
            }

        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getShortNameForController($name)
    {
        return camel_case(str_replace_last($this->options['postfix'], '', class_basename($name)));
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock          $docBlock
     *
     * @return string
     */
    protected function getMethodName($method, $docBlock = null)
    {
        if ($docBlock !== null && $docBlock->hasTag(self::API_METHOD_NAME)) {
            $tags = $docBlock->getTagsByName(self::API_METHOD_NAME);

            return (string)$tags[0];
        }

        $controllerName = $method->getDeclaringClass();

        return $this->getShortNameForController($controllerName->getName()) . '_' . $method->getName();
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock          $docBlock
     *
     * @return array
     */
    protected function getMethodParameters($method, $docBlock = null)
    {
        $result = [];

        /** @var \ReflectionParameter $param */
        foreach ($method->getParameters() as $param) {
            $parameter = ['name' => $param->getName()];
            if (PHP_VERSION_ID > 70000) {
                $parameter['type'] = (string)$param->getType();
            }
            $parameter['optional'] = $param->isOptional();
            if ($parameter['optional']) {
                $default = var_export($param->getDefaultValue(), true);

                if (false !== stripos($default, 'array')) {
                    $parameter['default'] = '[]';
                } else {
                    $parameter['default'] = $default;
                }
            }

            $result[$param->getName()] = $parameter;
        }

        if ($docBlock === null) {
            return array_values($result);
        }

        $params = $docBlock->getTagsByName(self::API_METHOD_DEFAULT_PARAMS);

        /** @var DocBlock\Tags\Param $param */
        foreach ($params as $param) {
            $name = (string)$param->getVariableName();
            $result[$name]['name'] = $name;

            if (!empty((string)$param->getDescription())) {
                $result[$name]['description'] = (string)$param->getDescription();
            }
            if (!empty((string)$param->getType())) {
                $result[$name]['type'] = (string)$param->getType();
                $result[$name]['types'] = explode('|', (string)$param->getType());
            }
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
    protected function getExtendedParameters($docBlock, array $current = [])
    {
        $name = (string)$docBlock->getVariableName();

        $nameParts = explode('.', $name);
        $variableName = array_shift($nameParts);

        if (!isset($current[$variableName])) {
            $current[$variableName] = [];
        }
        $parameter = &$current[$variableName];

        while ($key = array_shift($nameParts)) {
            $isArray = false;
            $variableName = $key;
            if (substr($variableName, -2) === '[]') {
                $variableName = substr($variableName, 0, -2);
                $isArray = true;
            }
            if (!isset($parameter['parameters'][$variableName])) {
                $parameter['parameters'][$variableName] = [];
            }

            $parameter['type'] = 'object';
            $parameter['types'] = ['object'];

            $parameter = &$parameter['parameters'][$variableName];

            if ($isArray) {
                $parameter['array'] = true;
            }
        }

        $parameter['name'] = $variableName;

        $type = $docBlock->getType();
        $parameter['type'] = (string)$type;
        $parameter['types'] = [(string)$type];

        if ($type instanceof Date) {
            $parameter['typeFormat'] = $type->getFormat();
        }

        if ($type instanceof Enum) {
            $parameter['typeVariants'] = $type->getVariants();
        }

        if ($type instanceof Array_) {
            $parameter['array'] = true;
            $parameter['type'] = (string)$type->getValueType();
            $parameter['types'] = [(string)$type->getValueType()];
        }

        $parameter['optional'] = $docBlock->isOptional();
        if (!empty((string)$docBlock->getDescription())) {
            $parameter['description'] = (string)$docBlock->getDescription();
        }

        if ($docBlock->hasDefault()) {
            $parameter['default'] = $docBlock->getDefaultValue();
        }
        if ($docBlock->hasExample()) {
            $parameter['example'] = $docBlock->getExampleValue();
        }

        return $current;
    }

    /**
     * @param ApiReturn $docBlock
     * @param array     $current
     *
     * @return array
     */
    protected function getExtendedReturns($docBlock, array $current = [])
    {
        $name = (string)$docBlock->getVariableName();

        $nameParts = explode('.', $name);
        $variableName = array_shift($nameParts);

        if (!isset($current[$variableName])) {
            $current[$variableName] = [];
        }
        $parameter = &$current[$variableName];

        while ($key = array_shift($nameParts)) {
            $isArray = false;
            $variableName = $key;
            if (substr($variableName, -2) === '[]') {
                $variableName = substr($variableName, 0, -2);
                $isArray = true;
            }
            if (!isset($parameter['parameters'][$variableName])) {
                $parameter['parameters'][$variableName] = [];
            }

            $parameter['type'] = 'object';
            $parameter['types'] = ['object'];

            $parameter = &$parameter['parameters'][$variableName];

            if ($isArray) {
                $parameter['array'] = true;
            }
        }

        $parameter['name'] = $variableName;

        $type = $docBlock->getType();
        $parameter['type'] = (string)$type;
        $parameter['types'] = [(string)$type];

        if ($type instanceof Date) {
            $parameter['typeFormat'] = $type->getFormat();
        }

        if ($type instanceof Enum) {
            $parameter['typeVariants'] = $type->getVariants();
        }

        if ($type instanceof Array_) {
            $parameter['array'] = true;
            $parameter['type'] = (string)$type->getValueType();
            $parameter['types'] = [(string)$type->getValueType()];
        }

        if (!empty((string)$docBlock->getDescription())) {
            $parameter['description'] = (string)$docBlock->getDescription();
        }

        return $current;
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock          $docBlock
     *
     * @return array
     */
    protected function getMethodReturn($method, $docBlock = null)
    {
        $result = ['type' => 'mixed'];

        if (PHP_VERSION_ID > 70000) {
            $result['type'] = $method->getReturnType();
        }

        if ($docBlock !== null && $docBlock->hasTag(self::API_METHOD_RETURN)) {
            /** @var DocBlock\Tags\Return_ $return */
            $return = $docBlock->getTagsByName(self::API_METHOD_RETURN)[0];
            if (!empty((string)$return->getType())) {
                $result['type'] = (string)$return->getType();
                $result['types'] = explode('|', (string)$return->getType());
            }
            if (!empty((string)$return->getDescription())) {
                $result['description'] = (string)$return->getDescription();
            }
        }

        return $result;
    }

    /**
     * @param DocBlock $docBlock
     *
     * @return string
     */
    protected function getMethodDescription($docBlock)
    {
        if ($docBlock->hasTag(self::API_METHOD_DESCRIPTION)) {
            $tags = $docBlock->getTagsByName(self::API_METHOD_DESCRIPTION);

            return (string)$tags[0];
        }

        return $docBlock->getSummary();
    }

    /**
     * @param ApiEnum $docBlock
     * @param array   $objects
     *
     * @return array
     */
    protected function getDocForEnum($docBlock, array $objects = [])
    {
        if (!isset($objects[$docBlock->getTypeName()])) {
            $objects[$docBlock->getTypeName()] = [
                'name'   => $docBlock->getTypeName(),
                'values' => [],
            ];
        }

        $objects[$docBlock->getTypeName()]['values'][] = [
            'value'       => $docBlock->getValue(),
            'description' => (string)$docBlock->getDescription(),
        ];

        return $objects;
    }

    /**
     * @param ApiObject $docBlock
     * @param array     $objects
     *
     * @return array
     */
    protected function getDocForObject($docBlock, array $objects = [])
    {
        $objectName = $docBlock->getObjectName();
        if (!isset($objects[$objectName])) {
            $objects[$objectName] = [
                'name'       => $objectName,
                'parameters' => [],
            ];
        }

        $objects[$objectName]['parameters'] = $this->getExtendedParameters($docBlock, $objects[$objectName]['parameters']);

        return $objects;
    }

    private function getControllers()
    {
        $namespace = trim($this->options['namespace'], '\\');
        $files = scandir($this->getNamespaceDirectory($namespace), SCANDIR_SORT_ASCENDING);

        $classes = array_map(function ($file) use ($namespace) {
            return $namespace . '\\' . str_replace('.php', '', $file);
        }, $files);

        return array_filter($classes, function ($possibleClass) {
            return class_exists($possibleClass);
        });
    }

    private function getDefinedNamespaces()
    {
        $composerJsonPath = app()->basePath() . DIRECTORY_SEPARATOR . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));

        return (array)$composerConfig->autoload->{'psr-4'};
    }

    private function getNamespaceDirectory($namespace)
    {
        $composerNamespaces = $this->getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';

            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                $path = app()->basePath() . DIRECTORY_SEPARATOR . $composerNamespaces[$possibleNamespace] . implode('/', array_reverse($undefinedNamespaceFragments));

                return realpath($path);
            }

            $undefinedNamespaceFragments[] = array_pop($namespaceFragments);
        }

        return false;
    }
}