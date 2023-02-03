<?php

namespace Tochka\JsonRpc\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;

interface ParserInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return array<JsonRpcServerRequest>
     */
    public function parse(ServerRequestInterface $request): array;
}
