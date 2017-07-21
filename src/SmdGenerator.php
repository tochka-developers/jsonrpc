<?php

namespace Tochka\JsonRpc;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use Tochka\JsonRpc\DocBlock\ApiReturn;
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

    protected $options = [];
    protected $acl = false;

    protected $default = [
        'transport' => 'POST',
        'envelope' => 'JSON-RPC-2.0',
        'SMDVersion' => '2.0',
        'contentType' => 'application/json',
        'generator' => 'Tochka/JsonRpc'
    ];

    /** @var DocBlockFactory */
    protected $docFactory = null;
    protected $additionalTags = [
        self::API_METHOD_RETURN_PARAM => ApiReturn::class,
    ];

    public function __construct($options)
    {
        $this->options = $options;
        $this->docFactory = DocBlockFactory::createInstance($this->additionalTags);
    }

    public function get()
    {
        $result = $this->default;

        $result['target'] = $this->options['uri'];
        $result['description'] = $this->options['description'];

        if ($this->options['auth']) {
            $result['additionalHeaders'] = [
                config('jsonrpc.accessHeaderName') => '<AuthToken>'
            ];
        }

        $result['namedParameters'] = in_array(AssociateParamsMiddleware::class, $this->options['middleware']);
        $result['acl'] = $this->acl = in_array(AccessControlListMiddleware::class, $this->options['middleware']);

        $result['services'] = $this->getServicesInfo();

        return $result;
    }

    /**
     * @return array
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
            }

            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->name !== $controller) continue;
                if (in_array($method->getName(), $ignoreMethods, true)) continue;

                $service = $this->generateDocForMethod($method);
                // получаем имя для группы методов
                $service['group'] = $group;
                if (!empty($groupName)) {
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
     * @return string
     */
    protected function getGroupName($docBlock)
    {
        if ($docBlock->hasTag(self::API_GROUP_NAME)) {
            $tags = $docBlock->getTagsByName(self::API_GROUP_NAME);
            return (string)$tags[0];
        } else {
            return $docBlock->getSummary();
        }
    }

    /**
     * @param \ReflectionMethod $method
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

            if ($docBlock->hasTag(self::API_METHOD_NOTE)) {
                $result['note'] = (string)$docBlock->getTagsByName(self::API_METHOD_NOTE)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_WARNING)) {
                $result['warning'] = (string)$docBlock->getTagsByName(self::API_METHOD_NOTE)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_REQUEST_EXAMPLE)) {
                $result['requestExample'] = (string)$docBlock->getTagsByName(self::API_METHOD_REQUEST_EXAMPLE)[0];
            }

            if ($docBlock->hasTag(self::API_METHOD_RESPONSE_EXAMPLE)) {
                $result['responseExample'] = (string)$docBlock->getTagsByName(self::API_METHOD_RESPONSE_EXAMPLE)[0];
            }

            $result['parameters'] = $this->getMethodParameters($method, $docBlock);
            $result['returns'] = $this->getMethodReturn($method, $docBlock);

            $returnParams = $docBlock->getTagsByName(self::API_METHOD_RETURN_PARAM);

            /** @var DocBlock\Tags\Param $param */
            foreach ($returnParams as $param) {
                $return = ['name' => $param->getVariableName()];
                if (!empty((string)$param->getDescription())) {
                    $return['description'] = (string)$param->getDescription();
                }
                if (!empty((string)$param->getType())) {
                    $return['type'] = (string)$param->getType();
                }

                $result['returnParams'][] = $return;
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
     * @return string
     */
    protected function getShortNameForController($name)
    {
        return snake_case(str_replace_last($this->options['postfix'], '', class_basename($name)));
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock $docBlock
     * @return string
     */
    protected function getMethodName($method, $docBlock = null)
    {
        if (!empty($docBlock) && $docBlock->hasTag(self::API_METHOD_NAME)) {
            $tags = $docBlock->getTagsByName(self::API_METHOD_NAME);
            return (string)$tags[0];
        } else {
            $controllerName = $method->getDeclaringClass();
            return $this->getShortNameForController($controllerName->getName()) . '_' . $method->getName();
        }
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock $docBlock
     * @return array
     */
    protected function getMethodParameters($method, $docBlock = null)
    {
        $result = [];

        /** @var \ReflectionParameter $param */
        foreach ($method->getParameters() as $param) {
            $parameter = ['name' => $param->getName()];
            if (version_compare(phpversion(), '7.0', '>')) {
                $parameter['type'] = (string)$param->getType();
            }
            $parameter['optional'] = $param->isOptional();
            if ($parameter['optional']) {
                $default = var_export($param->getDefaultValue(), true);

                if (preg_match('#array#iu', $default)) {
                    $parameter['default'] = '[]';
                } else {
                    $parameter['default'] = $default;
                }
            }

            $result[$param->getName()] = $parameter;
        }

        if (empty($docBlock)) {
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
            }
        }

        return array_values($result);
    }

    /**
     * @param \ReflectionMethod $method
     * @param DocBlock $docBlock
     * @return array
     */
    protected function getMethodReturn($method, $docBlock = null)
    {
        $result = ['type' => 'mixed'];

        if (version_compare(phpversion(), '7.0', '>')) {
            $result['type'] = $method->getReturnType();
        }

        if (!empty($docBlock) && $docBlock->hasTag(self::API_METHOD_RETURN)) {
            /** @var DocBlock\Tags\Return_ $return */
            $return = $docBlock->getTagsByName(self::API_METHOD_RETURN)[0];
            if (!empty((string)$return->getType())) {
                $result['type'] = (string)$return->getType();
            }
            if (!empty((string)$return->getDescription())) {
                $result['description'] = (string)$return->getDescription();
            }
        }

        return $result;
    }
    /**
     * @param DocBlock $docBlock
     * @return string
     */
    protected function getMethodDescription($docBlock)
    {
        if ($docBlock->hasTag(self::API_METHOD_DESCRIPTION)) {
            $tags = $docBlock->getTagsByName(self::API_METHOD_DESCRIPTION);
            return (string)$tags[0];
        } else {
            return $docBlock->getSummary();
        }
    }

    private function getControllers()
    {
        $namespace = trim($this->options['namespace'], '\\');
        $files = scandir($this->getNamespaceDirectory($namespace));

        $classes = array_map(function($file) use ($namespace){
            return $namespace . '\\' . str_replace('.php', '', $file);
        }, $files);

        return array_filter($classes, function($possibleClass) {
            return class_exists($possibleClass);
        });
    }

    private function getDefinedNamespaces()
    {
        $composerJsonPath = app()->basePath() . DIRECTORY_SEPARATOR . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));

        return (array) $composerConfig->autoload->{'psr-4'};
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