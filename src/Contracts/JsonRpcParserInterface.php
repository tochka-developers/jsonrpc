<?php

namespace Tochka\JsonRpc\Contracts;

interface JsonRpcParserInterface
{
    public function parse(string $content): array;
}
