<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Container\Container;
use Tochka\JsonRpc\Contracts\MiddlewareInterface;
use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;

class MiddlewareRegistry implements MiddlewareRegistryInterface
{
    /** @var array<string, array<MiddlewareInterface>> */
    private array $middleware = [];
    private Container $container;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addMiddlewaresFromConfig(string $serverName, ServerConfig $config): void
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($config->middleware as $key => $value) {
            if (is_string($value)) {
                $this->middleware[$serverName][] = $this->instantiateMiddleware($value);
            } elseif (is_string($key) && is_array($value)) {
                $this->middleware[$serverName][] = $this->instantiateMiddleware($key, $value);
            }
        }
    }

    public function getMiddleware(string $serverName, ?string $instanceOf = null): array
    {
        if (!array_key_exists($serverName, $this->middleware)) {
            return [];
        }

        if ($instanceOf === null) {
            return $this->middleware[$serverName];
        }

        return array_filter(
            $this->middleware[$serverName],
            function (MiddlewareInterface $middleware) use ($instanceOf) {
                return $middleware instanceof $instanceOf;
            }
        );
    }

    public function prependMiddleware(MiddlewareInterface $middleware, ?string $serverName = null): void
    {
        if ($serverName === null) {
            foreach ($this->middleware as $serverName => $_) {
                $this->prependMiddleware($middleware, $serverName);
            }
        } else {
            array_unshift($this->middleware[$serverName], $middleware);
        }
    }

    public function appendMiddleware(MiddlewareInterface $middleware, ?string $serverName = null): void
    {
        if ($serverName === null) {
            foreach ($this->middleware as $serverName => $_) {
                $this->appendMiddleware($middleware, $serverName);
            }
        } else {
            $this->middleware[$serverName][] = $middleware;
        }
    }

    public function addMiddlewareAfter(
        MiddlewareInterface $middleware,
        string $afterMiddleware,
        ?string $serverName = null
    ): void {
        if ($serverName === null) {
            foreach ($this->middleware as $serverName => $_) {
                $this->addMiddlewareAfter($middleware, $afterMiddleware, $serverName);
            }
        } else {
            $resultedMiddleware = [];
            $find = false;

            foreach ($this->middleware[$serverName] as $middlewareInstance) {
                $resultedMiddleware[] = $middlewareInstance;
                if ($middlewareInstance::class === $afterMiddleware) {
                    $find = true;
                    $resultedMiddleware[] = $middleware;
                }
            }

            if ($find) {
                $this->middleware[$serverName] = $resultedMiddleware;
            } else {
                $this->appendMiddleware($middleware, $serverName);
            }
        }
    }

    public function addMiddlewareBefore(
        MiddlewareInterface $middleware,
        string $beforeMiddleware,
        ?string $serverName = null
    ): void {
        if ($serverName === null) {
            foreach ($this->middleware as $serverName => $_) {
                $this->addMiddlewareBefore($middleware, $beforeMiddleware, $serverName);
            }
        } else {
            $resultedMiddleware = [];
            $find = false;

            foreach ($this->middleware[$serverName] as $middlewareInstance) {
                if (get_class($middlewareInstance) === $beforeMiddleware) {
                    $find = true;
                    $resultedMiddleware[] = $middleware;
                }

                $resultedMiddleware[] = $middlewareInstance;
            }

            if ($find) {
                $this->middleware[$serverName] = $resultedMiddleware;
            } else {
                $this->prependMiddleware($middleware, $serverName);
            }
        }
    }

    private function instantiateMiddleware(string $className, array $params = []): MiddlewareInterface
    {
        try {
            $instance = $this->container->make($className, $params);

            if (!$instance instanceof MiddlewareInterface) {
                throw new \RuntimeException(
                    sprintf('Middleware [%s] must implement [%s]', $className, MiddlewareInterface::class)
                );
            }

            return $instance;
        } catch (\Throwable $e) {
            throw InternalErrorException::from($e);
        }
    }
}
