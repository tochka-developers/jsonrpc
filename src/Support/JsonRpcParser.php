<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

class JsonRpcParser
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return JsonRpcRequest[]
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function parse(Request $request): array
    {
        // получаем тело запроса
        $json = $request->getContent();

        // если запрос пустой
        if (empty($json)) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        // декодируем json
        $data = json_decode($json, false);

        // если не валидный json
        if ($data === null) {
            throw new JsonRpcException(JsonRpcException::CODE_PARSE_ERROR);
        }

        if (!is_array($data)) {
            $calls = [$data];
        } else {
            $calls = $data;
        }

        return array_map(static function ($call) {
            return new JsonRpcRequest($call);
        }, $calls);
    }
}
