<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Exceptions\JsonRpcException;

class JsonRpcParser
{
    /**
     * @param string $content
     *
     * @return JsonRpcRequest[]
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function parse(string $content): array
    {
        // если запрос пустой
        if (empty($content)) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        // декодируем json
        $data = json_decode($content, false);

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
