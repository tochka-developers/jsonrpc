<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcRequest;

class AssociateParamsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  JsonRpcRequest $request
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle($request)
    {
        $api_params = !empty($request->call->params) ? (array)$request->call->params : [];

        // подготавливаем аргументы для вызова метода
        $reflectionMethod = new \ReflectionMethod($request->controller, $request->method);
        $errors = [];
        $args = [];

        foreach ($reflectionMethod->getParameters() as $i => $parameter) {

            $value = !empty($api_params[$parameter->getName()]) ? $api_params[$parameter->getName()] : null;

            // если аргумент не передан
            if (null === $value) {
                // если он обязателен
                if (!$parameter->isOptional()) {
                    $errors[] = [
                        'code' => 'required_field',
                        'message' => 'Не передан либо пустой обязательный параметр',
                        'object_name' => $parameter->getName()
                    ];
                } else {
                    // получим значение аргумента по умолчанию
                    $value = $parameter->getDefaultValue();
                }
            } else {
                // Проверяем тип
                $parameterType = strtolower(class_basename((string)$parameter->getType()));
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

                if (gettype($value) !== $parameterType) {
                    $errors[] = [
                        'code' => 'invalid_parameter',
                        'message' => 'Передан аргумент неверного типа',
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

        $request->params = $args;

        return true;
    }
}
