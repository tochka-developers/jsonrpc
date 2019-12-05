<?php

namespace Tochka\JsonRpc\Middleware;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcRequest;

class MethodClosureMiddleware implements BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param JsonRpcRequest $request
     *
     * @return mixed
     * @throws JsonRpcException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle($request)
    {
        // смотрим метод, к которому пытаются обратиться
        $namespace = $request->server->namespace;
        $method = $request->call->method;

        if (!empty($request->call->endpoint) && !empty($request->call->action)) {
            $namespace = $request->server->namespace . Str::studly($request->call->endpoint) . '\\';
            $controllerName = $request->call->action;
        } elseif (!empty($request->call->endpoint)) {
            $controllerName = $request->call->endpoint;
        } else {
            $methodCall = $request->call->method;

            // парсим имя метода
            $methodArray = explode('_', $methodCall);

            // если имя вызываемого метода без разделителя - значит ищем его в базовом классе
            if (\count($methodArray) === 1) {
                $controllerName = $request->server->controller;
                $method = $methodCall;
            } else {
                $controllerName = $methodArray[0];
                unset($methodArray[0]);
                $method = Str::camel(implode('_', $methodArray));
            }

        }

        $controllerName = $namespace . Str::studly($controllerName . $request->server->postfix);

        // если нет такого контроллера или метода
        if (!class_exists($controllerName)) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }

        $controller = Container::getInstance()->make($controllerName);

        if (!\is_callable([$controller, $method])) {
            throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
        }

        $request->controller = $controller;
        $request->method = $method;
        $request->params = !empty($request->call->params) ? array_values((array) $request->call->params) : [];

        return true;
    }
}
