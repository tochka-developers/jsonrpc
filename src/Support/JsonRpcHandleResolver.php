<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\JsonRpcRequestInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Facades\JsonRpcRequestCast as JsonRpcRequestCastFacade;

class JsonRpcHandleResolver implements HandleResolverInterface
{
    public const PARAMS_RESOLVER_DTO = 'dto';
    public const PARAMS_RESOLVER_BY_METHOD = 'by-method';

    /**
     * @param JsonRpcRequest                       $request
     * @param \Tochka\JsonRpc\Support\ServerConfig $config
     * @param string|null                          $group
     * @param string|null                          $action
     *
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function resolve(
        JsonRpcRequest $request,
        ServerConfig $config,
        string $group = null,
        string $action = null
    ): bool {
        if (empty($request->call->jsonrpc) || $request->call->jsonrpc !== '2.0' || empty($request->call->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        [$controllerName, $method] = $this->getHandledMethod($request, $config, $group, $action);

        $request->controller = $this->initializeController($controllerName, $method, $request);
        $request->method = $method;

        if ($config->paramsResolver === self::PARAMS_RESOLVER_DTO) {
            $request->params = $this->castParamsToDTO($request);
        } else {
            $request->params = $this->getCallParams($request);
        }

        return true;
    }

    /**
     * @throws \ReflectionException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function castParamsToDTO(JsonRpcRequest $request): array
    {
        $reflectionMethod = new \ReflectionMethod($request->controller, $request->method);

        $args = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType) {
                $diName = $type->getName();

                if (\in_array(JsonRpcRequestInterface::class, class_implements($diName), true)) {
                    $instance = JsonRpcRequestCastFacade::cast($diName, $request->call->params);
                } else {
                    $instance = Container::getInstance()->make($type);
                }

                $args[] = $instance;
            }
        }

        return $args;
    }

    /**
     * @param JsonRpcRequest $request
     *
     * @return array
     * @throws \ReflectionException
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    protected function getCallParams(JsonRpcRequest $request): array
    {
        $api_params = !empty($request->call->params) ? (array)$request->call->params : [];

        // подготавливаем аргументы для вызова метода
        $reflectionMethod = new \ReflectionMethod($request->controller, $request->method);
        $errors = [];
        $args = [];

        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $value = $api_params[$parameter->getName()] ?? null;

            // если аргумент не передан
            if ($value === null) {
                // если он обязателен
                if (!$parameter->isOptional()) {
                    $errors[] = [
                        'code'        => 'required_field',
                        'message'     => 'Не передан либо пустой обязательный параметр',
                        'object_name' => $parameter->getName(),
                    ];
                } else {
                    // получим значение аргумента по умолчанию
                    $value = $parameter->getDefaultValue();
                }
            } else {
                // Проверяем тип
                $type = $parameter->getType();
                if ($type) {
                    $parameterType = $this->getCanonicalTypeName($type->getName());

                    if (gettype($value) !== $parameterType) {
                        $errors[] = [
                            'code'        => 'invalid_parameter',
                            'message'     => 'Передан аргумент неверного типа',
                            'object_name' => $parameter->getName(),
                        ];
                    }
                }
            }

            // установим переданное значение
            $args[$i] = $value;
        }

        if (count($errors) > 0) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_PARAMETERS, null, $errors);
        }

        return $args;
    }

    /**
     * @param JsonRpcRequest                       $request
     * @param \Tochka\JsonRpc\Support\ServerConfig $config
     * @param string|null                          $group
     * @param string|null                          $action
     *
     * @return array
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    protected function getHandledMethod(
        JsonRpcRequest $request,
        ServerConfig $config,
        string $group = null,
        string $action = null
    ): array {
        $method = $request->call->method;

        $namespace = trim($config->namespace, '\\');

        if ($group !== null) {
            $namespace .= '\\' . Str::studly($group);
        }

        if ($action !== null) {
            $controllerName = $action;
        } else {
            $methodCall = $request->call->method;

            // парсим имя метода
            $methodArray = explode($config->methodDelimiter, $methodCall);

            if (count($methodArray) < 2) {
                throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
            }

            $controllerName = array_shift($methodArray);
            $method = Str::camel(implode('_', $methodArray));
        }

        $controllerName = $namespace . '\\' . Str::studly($controllerName . $config->controllerSuffix);

        return [$controllerName, $method];
    }

    /**
     * @param string         $controllerName
     * @param string         $method
     * @param JsonRpcRequest $request
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    protected function initializeController(string $controllerName, string $method, JsonRpcRequest $request)
    {
        // если нет такого контроллера или метода
        if (!class_exists($controllerName)) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }

        $controller = Container::getInstance()->make($controllerName);

        if (!is_callable([$controller, $method]) || $method === 'setJsonRpcRequest') {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }

        if (method_exists($controller, 'setJsonRpcRequest')) {
            $controller->setJsonRpcRequest($request);
        }

        return $controller;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getCanonicalTypeName(string $type): string
    {
        $parameterType = strtolower(class_basename($type));
        switch ($parameterType) {
            case 'str':
            case 'string':
                $parameterType = 'string';
                break;
            case 'int':
            case 'integer':
                $parameterType = 'integer';
                break;
            case 'float':
            case 'double':
                $parameterType = 'double';
                break;
            case 'boolean':
            case 'bool':
                $parameterType = 'boolean';
                break;
            case 'stdclass':
                $parameterType = 'object';
                break;
        }

        return $parameterType;
    }
}
