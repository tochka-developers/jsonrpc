<?php

namespace Tochka\JsonRpc\Helpers;

use Illuminate\Support\Facades\App;
use Psr\SimpleCache\CacheInterface;

class ArrayFileCache implements CacheInterface
{
    private ?array $data = null;
    private string $cacheName;
    private string $cachePath;
    
    public function __construct(string $cacheName, ?string $cachePath = null)
    {
        $this->cacheName = $cacheName;
        if ($cachePath === null) {
            $this->cachePath = App::bootstrapPath('cache');
        } else {
            $this->cachePath = $cachePath;
        }
    }
    
    protected function loadAllData(): void
    {
        if ($this->data !== null) {
            return;
        }
        
        $filePath = $this->getCacheFilePath();
        if (file_exists($filePath)) {
            $this->data = require $filePath;
        } else {
            $this->data = [];
        }
    }
    
    protected function saveAllData(): void
    {
        if (!is_dir($this->cachePath) && !mkdir($this->cachePath) && !is_dir($this->cachePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->cachePath));
        }
        
        file_put_contents($this->getCacheFilePath(), '<?php return ' . var_export($this->data, true) . ';' . PHP_EOL);
    }
    
    public function get($key, $default = null)
    {
        $this->loadAllData();
        
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        $this->loadAllData();
        
        $this->data[$key] = $value;
        
        $this->saveAllData();
    }
    
    public function delete($key)
    {
        $this->loadAllData();
        
        unset($this->data[$key]);
        
        $this->saveAllData();
    }
    
    public function getMultiple($keys, $default = null): array
    {
        $this->loadAllData();
        
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        
        return $result;
    }
    
    public function setMultiple($values, $ttl = null)
    {
        $this->loadAllData();
        
        $this->data = array_merge($this->data, $values);
        
        $this->saveAllData();
    }
    
    public function deleteMultiple($keys)
    {
        $this->loadAllData();
        
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }
        
        $this->saveAllData();
    }
    
    public function clear(): void
    {
        $this->data = [];
        
        $filePath = $this->getCacheFilePath();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    public function has($key): bool
    {
        $this->loadAllData();
        
        return array_key_exists($key, $this->data);
    }
    
    private function getCacheFilePath(): string
    {
        return rtrim($this->cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->cacheName . '.php';
    }
}
