<?php

namespace Tochka\JsonRpc\Route;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ControllerFinder
{
    /** @var array<string> */
    private ?array $definedNamespaces = null;
    
    /**
     * @return array<string>
     * @throws \JsonException
     */
    public function find(string $namespace, string $suffix = ''): array
    {
        $namespace = trim($namespace, '\\');
        $namespaceDirectory = $this->getNamespaceDirectory($namespace);
        if ($namespaceDirectory === null) {
            return [];
        }
        
        $files = scandir($this->getNamespaceDirectory($namespace), SCANDIR_SORT_ASCENDING);
        
        $controllers = [];
        $controllerList = [];
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $directory = $this->getNamespaceDirectory($namespace . '\\' . $file);
            if ($directory === null) {
                continue;
            }
            
            if (is_dir($directory)) {
                $controllerList[] = $this->find($namespace . '\\' . $file, $suffix);
            } else {
                $className = $namespace . '\\' . str_replace('.php', '', $file);
                if (Str::endsWith($className, $suffix) && class_exists($className)) {
                    $controllers[] = $className;
                }
            }
        }
        
        return array_merge($controllers, ...$controllerList);
    }
    
    /**
     * @param string $namespace
     *
     * @return string|null
     * @throws \JsonException
     */
    protected function getNamespaceDirectory(string $namespace): ?string
    {
        $composerNamespaces = $this->getDefinedNamespaces();
        
        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];
        
        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';
            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                $path = App::basePath() . DIRECTORY_SEPARATOR . $composerNamespaces[$possibleNamespace] . implode(
                        '/',
                        array_reverse($undefinedNamespaceFragments)
                    );
                
                $realPath = realpath($path);
                
                return $realPath !== false ? $realPath : null;
            }
            
            $undefinedNamespaceFragments[] = array_pop($namespaceFragments);
        }
        
        return null;
    }
    
    /**
     * @throws \JsonException
     */
    protected function getDefinedNamespaces(): array
    {
        if ($this->definedNamespaces === null) {
            $composerJsonPath = App::basePath() . DIRECTORY_SEPARATOR . 'composer.json';
            $composerConfig = json_decode(file_get_contents($composerJsonPath), false, 512, JSON_THROW_ON_ERROR);
            $this->definedNamespaces = (array)$composerConfig->autoload->{'psr-4'};
        }
        
        return $this->definedNamespaces;
    }
}
