<?php

namespace Tochka\JsonRpc\Route;

use Illuminate\Support\Str;

final class ControllerFinder
{
    /** @var array<string, string> */
    private ?array $definedNamespaces = null;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly string $appBasePath
    ) {
    }

    /**
     * @return array<class-string>
     * @throws \JsonException
     */
    public function find(string $namespace, string $suffix = ''): array
    {
        $namespace = trim($namespace, '\\');
        $namespaceDirectory = $this->getNamespaceDirectory($namespace);
        if ($namespaceDirectory === null) {
            return [];
        }

        $files = scandir($namespaceDirectory, SCANDIR_SORT_ASCENDING);

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
    private function getNamespaceDirectory(string $namespace): ?string
    {
        $composerNamespaces = $this->getDefinedNamespaces();

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';
            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                $path = $this->appBasePath . DIRECTORY_SEPARATOR . $composerNamespaces[$possibleNamespace] . implode(
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
     * @return array<string, string>
     * @throws \JsonException
     */
    private function getDefinedNamespaces(): array
    {
        if ($this->definedNamespaces === null) {
            $composerJsonPath = $this->appBasePath . DIRECTORY_SEPARATOR . 'composer.json';
            /** @var array{autoload: array{"psr-4": array<string, string>}} $composerConfig */
            $composerConfig = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
            $this->definedNamespaces = $composerConfig['autoload']['psr-4'] ?? [];
        }

        return $this->definedNamespaces;
    }
}
