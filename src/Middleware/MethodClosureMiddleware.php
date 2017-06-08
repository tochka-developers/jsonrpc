<?php

namespace Tochka\JsonRpc\Middleware;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Closure;
use Tochka\JsonRpc\JsonRpcRequest;

class MethodClosureMiddleware
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
        // смотрим метод, к которому пытаются обратиться
        $methodCall = $request->call->method;

        // парсим имя метода
        $methodArray = explode('_', $methodCall);

        // если имя вызываемого метода без разделителя - значит ищем его в базовом классе
        if (count($methodArray) === 1) {
            $controllerName = 'Api';
            $method = $methodCall;
        } else {
            $controllerName = $methodArray[0];
            unset($methodArray[0]);
            $method = camel_case(implode('_', $methodArray));
        }

        $controllerName = config('jsonrpc.controllerNamespace') . studly_case($controllerName . 'Controller');

        // если нет такого контроллера или метода
        if (!class_exists($controllerName)) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }

        $controller = app($controllerName);

        if (!method_exists($controller, $method)) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }

        $request->controller = $controller;
        $request->method = $method;
        $request->params = array_values((array) $request->call->params) ?? [];

        return true;
    }
}
