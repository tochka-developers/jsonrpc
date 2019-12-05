<?php

namespace Tochka\JsonRpc\Handlers;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Tochka\JsonRpc\Facades\JsonRpcHandler;
use Tochka\JsonRpc\JsonRpcRequest;
use Tochka\JsonRpc\JsonRpcServer;

class ExecuteRequestHandler implements BaseHandler
{
    /**
     * Handle an incoming request.
     *
     * @param Request       $request
     * @param JsonRpcServer $server
     *
     * @return mixed
     */
    public function handle(Request $request, JsonRpcServer $server)
    {
        $data = $server->data;

        // если один вызов - приведем к массиву вызовов
        if (!\is_array($data)) {
            $calls = [$data];
        } else {
            $calls = $data;
        }

        $result = [];

        // выполняем все вызовы
        foreach ($calls as $call) {
            // создаем ответ
            $answer = new \stdClass();
            $answer->jsonrpc = '2.0';

            if (!empty($server->endpoint)) {
                $call->endpoint = $server->endpoint;
            }

            if (!empty($server->action)) {
                $call->action = $server->action;
            }

            // создаем запрос
            $jsonRpcRequest = new JsonRpcRequest($call, $server);
            $jsonRpcRequest->service = $server->serviceName;

            Container::getInstance()->instance(JsonRpcRequest::class, $jsonRpcRequest);

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

        $server->setResponse($result);

        return true;
    }
}
