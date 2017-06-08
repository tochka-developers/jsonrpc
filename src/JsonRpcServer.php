<?php

namespace Tochka\JsonRpc;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Facades\JsonRpcHandler;
use Tochka\JsonRpc\Facades\JsonRpcLog;

/**
 * Class JsonRpcServer
 * @package Tochka\JsonRpc
 */
class JsonRpcServer
{
    public function handle(\Illuminate\Http\Request $request)
    {
        $result = [];

        try {
            // проверка типа метода
            if (!$request->isMethod('post')) {
                throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
            }

            // если включена аутентификацию - проверяем ключ доступа
            if (config('jsonrpc.authValidate', true)) {
                $serviceName = $this->auth($request);
            } else {
                $serviceName = 'guest';
            }

            // получаем тело запроса
            $json = $request->getContent();

            // если запрос пустой
            if(empty($json)){
                throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
            }

            // декодируем json
            $data = json_decode($json);
            JsonRpcLog::info('Request', (array)$data);

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
                $jsonRpcRequest = new JsonRpcRequest($call);
                $jsonRpcRequest->service = $serviceName;
                if (null !== $jsonRpcRequest->id) {
                    $answer->id = $jsonRpcRequest->id;
                }

                // выполняем запрос
                try {
                    $answer->result = $jsonRpcRequest->handle();

                    $message = sprintf('Successful request to method "%s" (id-%s) with params: ', $jsonRpcRequest->method, $jsonRpcRequest->id);
                    JsonRpcLog::info($message, $jsonRpcRequest->params);
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
     * @param \Illuminate\Http\Request $request
     * @return mixed
     * @throws JsonRpcException
     */
    protected function auth(\Illuminate\Http\Request $request)
    {
        if (!$key = $request->header(config('jsonrpc.accessHeaderName'))) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        $service = array_search($key, config('jsonrpc.keys'), true);

        if ($service === false) {
            throw new JsonRpcException(JsonRpcException::CODE_UNAUTHORIZED);
        }

        JsonRpcLog::info('Success auth', compact('service'));

        return $service;
    }
}