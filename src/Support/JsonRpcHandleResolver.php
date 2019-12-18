<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

class JsonRpcHandleResolver
{
    protected $controllerSuffix = 'Controller';

    public function setControllerSuffix(string $suffix): void
    {
        $this->controllerSuffix = $suffix;
    }

    /**
     * @param JsonRpcRequest $request
     * @param string|null    $group
     * @param string|null    $action
     *
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     * @throws \ReflectionException
     */
    public function resolve(JsonRpcRequest $request, string $group = null, string $action = null): bool
    {
        if (empty($request->call->jsonrpc) || $request->call->jsonrpc !== '2.0' || empty($request->call->method)) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        $method = $request->call->method;

        $namespace = Route::current()->getAction('namespace');
        $namespace = trim($namespace, '\\');

        if ($group !== null) {
            $namespace .= '\\' . Str::studly($group);
        }

        if ($action !== null) {
            $controllerName = $action;
        } else {
            $methodCall = $request->call->method;

            // парсим имя метода
            $methodArray = explode('_', $methodCall);

            if (count($methodArray) < 2) {
                throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
            }

            $controllerName = array_shift($methodArray);
            $method = Str::camel(implode('_', $methodArray));
        }

        $controllerName = $namespace . '\\' . Str::studly($controllerName . $this->controllerSuffix);

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

        $request->controller = $controller;
        $request->method = $method;
        $request->params = $this->getCallParams($request);

        return true;
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
        $api_params = !empty($request->call->params) ? (array) $request->call->params : [];

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
                $parameterType = strtolower(class_basename((string) $parameter->getType()));
                switch ($parameterType) {
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

                if (null !== $parameter->getType() && gettype($value) !== $parameterType) {
                    $errors[] = [
                        'code'        => 'invalid_parameter',
                        'message'     => 'Передан аргумент неверного типа',
                        'object_name' => $parameter->getName(),
                    ];
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
}
