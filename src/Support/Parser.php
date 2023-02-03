<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\ParserInterface;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;
use Tochka\JsonRpc\Standard\Exceptions\InvalidRequestException;
use Tochka\JsonRpc\Standard\Exceptions\ParseErrorException;

class Parser implements ParserInterface
{
    /**
     * @return array<JsonRpcServerRequest>
     */
    public function parse(ServerRequestInterface $request): array
    {
        $content = (string)$request->getBody();

        if (empty($content)) {
            throw InvalidRequestException::from('<root>', 'Empty request');
        }

        try {
            /** @var object $data */
            $data = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new ParseErrorException(previous: $e);
        }

        $calls = Arr::wrap($data);

        return array_map(
            fn (object $rawRequest) => new JsonRpcServerRequest($request, JsonRpcRequest::from($rawRequest)),
            $calls
        );
    }
}
