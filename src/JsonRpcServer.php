<?php

namespace Tochka\JsonRpc;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Facades\JsonRpcHandler;
use Tochka\JsonRpc\Facades\JsonRpcLog;

/**
 * Class JsonRpcServer
 * @package Tochka\JsonRpc
 */
class JsonRpcServer
{
    public function handle(Request $request, $options = [])
    {
        $options = $this->fillOptions($options);

        // SMD-схема
        if (array_key_exists('smd', $request->all())) {
            return (new SmdGenerator($options))->get();
        }

        $result = [];

        try {
            // проверка типа метода
            if (!$request->isMethod('post')) {
                throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
            }

            // если включена аутентификацию - проверяем ключ доступа
            $serviceName = 'guest';
            if ($options['auth']) {
                $serviceName = $this->auth($request);
            }

            // получаем тело запроса
            $json = $request->getContent();

            // если запрос пустой
            if(empty($json)){
                throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
            }

            // декодируем json
            $data = json_decode($json);

            // если не валидный json
            if (null === $data) {
                throw new JsonRpcException(JsonRpcException::CODE_PARSE_ERROR);
            }

            // если один вызов - приведем к массиву вызовов
            if (!is_array($data)) {
                $calls = [$data];
            } else {
                $calls = $data;
            }

            // выполняем все вызовы
            foreach ($calls as $call) {
                // создаем ответ
                $answer = new \stdClass();
                $answer->jsonrpc = '2.0';

                // создаем запрос
                $jsonRpcRequest = new JsonRpcRequest($call, $options);
                $jsonRpcRequest->service = $serviceName;

                app()->instance('JsonRpcRequest', $jsonRpcRequest);

                if (null !== $jsonRpcRequest->id) {
                    $answer->id = $jsonRpcRequest->id;
                }

                // выполняем запрос
                try {
                    $answer->result = $jsonRpcRequest->handle();
                } catch (\Exception $e) {
                    $answer->error = JsonRpcHandler::handle($e);
                }

                $result[] = $answer;
            }
        } catch (\Exception $e) {
            $answer = new \StdClass();
            $answer->jsonrpc = '2.0';
            $answer->error = JsonRpcHandler::handle($e);
            $result[] = $answer;
        }

        return count($result) > 1 ? $result : (array)$result[0];
    }

    /**
     * Проверка заголовка для идентификации сервиса
     * @param Request $request
     * @return mixed
     * @throws JsonRpcException
     */
    protected function auth(Request $request)
    {
        if (!$key = $request->header(config('jsonrpc.accessHeaderName', 'Access-Key'))) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        $service = array_search($key, config('jsonrpc.keys', []), true);

        if ($service === false) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        JsonRpcLog::info('Success auth', compact('service'));

        return $service;
    }

    /**
     * Заполняет параметры
     * @param array $options
     * @return array
     */
    protected function fillOptions($options)
    {
        if (empty($options['uri'])) {
            $options['uri'] = '/';
        }

        if (empty($options['namespace'])) {
            $options['namespace'] = config('jsonrpc.controllerNamespace', 'App\\Http\\Controllers\\Api\\');
        }

        if (empty($options['postfix'])) {
            $options['postfix'] = config('jsonrpc.controllerPostfix', 'Controller');
        }

        if (empty($options['description'])) {
            $options['description'] = config('jsonrpc.description', 'JsonRpc Server');
        }

        if (empty($options['controller'])) {
            $options['controller'] = config('jsonrpc.defaultController', 'Api');
        }

        if (empty($options['middleware'])) {
            $options['middleware'] = config('jsonrpc.middleware', [\Tochka\JsonRpc\Middleware\MethodClosureMiddleware::class]);
        }

        if (!isset($options['acl'])) {
            $options['acl'] = config('jsonrpc.acl', []);
        }

        if (!isset($options['auth'])) {
            $options['auth'] = config('jsonrpc.authValidate', true);
        }

        return $options;
    }
}