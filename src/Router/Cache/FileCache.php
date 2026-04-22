<?php

namespace Tochka\JsonRpc\Router\Cache;

use Illuminate\Support\Facades\App;
use Psr\SimpleCache\CacheInterface;

class FileCache implements CacheInterface
{
    protected string $cachePath;
    protected string $extension;
    
    public function __construct(?string $cachePath = null, ?string $extension = '')
    {
        $this->cachePath = $cachePath ?? App::bootstrapPath('cache');
        $this->extension = $extension;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->getCacheFilePath($key);
        if (!file_exists($filePath)) {
            return $default;
        }
        return file_get_contents($filePath);
    }
    
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if (!is_dir($this->cachePath) && !mkdir($this->cachePath) && !is_dir($this->cachePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->cachePath));
        }
        
        return (bool)file_put_contents($this->getCacheFilePath($key), $value);
    }
    
    public function delete(string $key): bool
    {
        if ($this->has($key)) {
            return unlink($this->getCacheFilePath($key));
        }
        return true;
    }
    
    public function clear(): bool
    {
        return false;
    }
    
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        
        return $result;
    }
    
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        $results = [];
        foreach ($values as $key => $value) {
            $results[] = $this->set($key, $value);
        }
        
        return !\in_array(false, $results, true);
    }
    
    public function deleteMultiple(iterable $keys): bool
    {
        $results = [];
        foreach ($keys as $key) {
            $results[] = $this->delete($key);
        }
        
        return !\in_array(false, $results, true);
    }
    
    public function has(string $key): bool
    {
        return file_exists($this->getCacheFilePath($key));
    }
    
    private function getCacheFilePath(string $fileName): string
    {
        return rtrim(
                $this->cachePath,
                DIRECTORY_SEPARATOR
            ) . DIRECTORY_SEPARATOR . 'jsonrpc_' . $fileName . $this->extension;
    }
}
