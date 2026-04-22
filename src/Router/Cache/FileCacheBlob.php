<?php

namespace Tochka\JsonRpc\Router\Cache;

class FileCacheBlob extends FileCache implements RouterCacheContract
{
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        return parent::set($key, serialize($value), $ttl);
    }
    
    public function get(string $key, mixed $default = null): array|null {
        $result = parent::get($key, $default);
        return $result ? unserialize($result) : $result;
    }
}
