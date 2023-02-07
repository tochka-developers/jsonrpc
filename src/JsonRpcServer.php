<?php

namespace Tochka\JsonRpc;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\ExceptionHandlerInterface;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\HttpRequestMiddlewareInterface;
use Tochka\JsonRpc\Contracts\JsonRpcRequestMiddlewareInterface;
use Tochka\JsonRpc\Contracts\JsonRpcServerInterface;
use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;
use Tochka\JsonRpc\Contracts\ParserInterface;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Facades\JsonRpcRouter;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Standard\Exceptions\MethodNotFoundException;

class JsonRpcServer implements JsonRpcServerInterface
{
    private ParserInterface $parser;
    private HandleResolverInterface $resolver;
    private Container $container;
    private ExceptionHandlerInterface $exceptionHandler;
    private MiddlewareRegistryInterface $middlewareRegistry;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        ParserInterface $parser,
        HandleResolverInterface $resolver,
        ExceptionHandlerInterface $exceptionHandler,
        MiddlewareRegistryInterface $middlewareRegistry,
        Container $container
    ) {
        $this->parser = $parser;
        $this->resolver = $resolver;
        $this->container = $container;
        $this->exceptionHandler = $exceptionHandler;
        $this->middlewareRegistry = $middlewareRegistry;
    }

    public function handle(
        ServerRequestInterface $request,
        string $serverName = 'default',
        string $group = null,
        string $action = null
    ): JsonRpcResponseCollection {
        try {
            $pipeline = new Pipeline($this->container);

            /** @var JsonRpcResponseCollection $responses */
            $responses = $pipeline->send($request)
                ->through($this->middlewareRegistry->getMiddleware($serverName, HttpRequestMiddlewareInterface::class))
                ->via('handleHttpRequest')
                ->then(
                    function (ServerRequestInterface $httpRequest) use ($serverName, $group, $action) {
                        $requests = $this->parser->parse($httpRequest);

                        $responses = new JsonRpcResponseCollection();

                        foreach ($requests as $request) {
                            $response = $this->handleRequest($request, $serverName, $group, $action);

                            if ($response !== null) {
                                $responses->add($response);
                            }
                        }

                        return $responses;
                    }
                );
        } catch (\Throwable $e) {
            $responses = new JsonRpcResponseCollection();

            $responses->add(
                new JsonRpcResponse(
                    id:    'empty',
                    error: $this->exceptionHandler->handle($e)
                )
            );
        }

        return $responses;
    }

    public function handleRequest(
        JsonRpcServerRequest $request,
        string $serverName,
        string $group = null,
        string $action = null
    ): ?JsonRpcResponse {
        try {
            $pipeline = new Pipeline($this->container);

            $route = JsonRpcRouter::get($serverName, $request->getJsonRpcRequest()->method, $group, $action);

            if ($route === null) {
                throw new MethodNotFoundException();
            }

            $request->setRoute($route);

            /** @var JsonRpcResponse|null */
            return $pipeline->send($request)
                ->through(
                    $this->middlewareRegistry->getMiddleware($serverName, JsonRpcRequestMiddlewareInterface::class)
                )
                ->via('handleJsonRpcRequest')
                ->then(
                    function (JsonRpcServerRequest $request) {
                        /** @psalm-suppress MixedAssignment */
                        $result = $this->resolver->handle($request);

                        if ($request->getJsonRpcRequest()->id === null) {
                            return null;
                        }

                        return new JsonRpcResponse(
                            id:     $request->getJsonRpcRequest()->id,
                            result: $result
                        );
                    }
                );
        } catch (\Throwable $e) {
            return new JsonRpcResponse(
                id:    $request->getJsonRpcRequest()->id ?? 'empty',
                error: $this->exceptionHandler->handle($e)
            );
        }
    }
}
