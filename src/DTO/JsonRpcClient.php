<?php

namespace Tochka\JsonRpc\DTO;

class JsonRpcClient
{
    public const GUEST = 'guest';

    private string $name;

    public function __construct(string $name = self::GUEST)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
