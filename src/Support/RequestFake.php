<?php

namespace Tochka\JsonRpc\Support;

trait RequestFake
{
    public static function fake(
        object|array $params = null,
        string|null $method = null,
        string|int|null $id = null,
        string|null $jsonrpc = null,
    ): self {
        $raw = (object)[
            'jsonrpc' => $jsonrpc ?? '2.0',
            'id' => $id ?? rand(1, 99999999),
            'method' => $method ?? 'method_name',
            'params' => $params ?? (object)[],
        ];
        
        return new self($raw);
    }
}
