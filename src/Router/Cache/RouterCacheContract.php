<?php

namespace Tochka\JsonRpc\Router\Cache;

use Psr\SimpleCache\CacheInterface;

interface RouterCacheContract extends CacheInterface
{
    public function get(string $key, mixed $default = null): array|null;
}
