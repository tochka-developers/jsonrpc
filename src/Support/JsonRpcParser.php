<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\JsonRpcParserInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Middleware\TokenAuthMiddleware;

class JsonRpcParser implements JsonRpcParserInterface
{
    /**
     * @throws JsonRpcException
     */
    public function parse(ServerRequestInterface $request): array
    {
        $content = (string)$request->getBody();
        
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
            fn($rawRequest) => $this->parseRequestFromRaw($request, $rawRequest),
            $calls
        );
    }
    
    /**
     * @throws JsonRpcException
     */
    private function parseRequestFromRaw(ServerRequestInterface $httpRequest, object $rawRequest): JsonRpcRequest
    {
        if (
            empty($rawRequest->jsonrpc)
            || $rawRequest->jsonrpc !== '2.0'
            || empty($rawRequest->method)
        ) {
            throw new JsonRpcException(JsonRpcException::CODE_INVALID_REQUEST);
        }
        
        $request = new JsonRpcRequest($httpRequest, $rawRequest);
        
        $auth = $httpRequest->getAttribute(TokenAuthMiddleware::ATTRIBUTE_AUTH);
        if ($auth !== null) {
            $request->setAuthName($auth);
        }
        
        return $request;
    }
}
