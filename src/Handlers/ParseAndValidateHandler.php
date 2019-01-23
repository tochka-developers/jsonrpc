<?php

namespace Tochka\JsonRpc\Handlers;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\JsonRpcServer;

class ParseAndValidateHandler implements BaseHandler
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     *
     * @param JsonRpcServer $server
     *
     * @return mixed
     * @throws JsonRpcException
     */
    public function handle(Request $request, JsonRpcServer $server)
    {
        // проверка типа метода
        if (!$request->isMethod('post')) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        // получаем тело запроса
        $json = $request->getContent();

        // если запрос пустой
        if (empty($json)) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        // декодируем json
        $data = json_decode($json);

        // если не валидный json
        if (null === $data) {
            throw new JsonRpcException(JsonRpcException::CODE_PARSE_ERROR);
        }

        $server->data = $data;

        return true;
    }
}
