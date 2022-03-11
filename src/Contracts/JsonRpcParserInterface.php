<?php

namespace Tochka\JsonRpc\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Support\JsonRpcRequest;

interface JsonRpcParserInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return array<JsonRpcRequest>
     */
    public function parse(ServerRequestInterface $request): array;
}
