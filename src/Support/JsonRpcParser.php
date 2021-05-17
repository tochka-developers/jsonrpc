<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Support\Arr;
use Tochka\JsonRpc\Contracts\JsonRpcParserInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

class JsonRpcParser implements JsonRpcParserInterface
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
        try {
            $data = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new JsonRpcException(JsonRpcException::CODE_PARSE_ERROR);
        }

        $calls = Arr::wrap($data);

        return array_map(
            fn($call) => new JsonRpcRequest($call),
            $calls
        );
    }
}
