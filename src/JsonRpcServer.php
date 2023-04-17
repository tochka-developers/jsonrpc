<?php

namespace Tochka\JsonRpc;

use Illuminate\Container\Container;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\JsonRpcParserInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Facades\ExceptionHandler;
use Tochka\JsonRpc\Facades\JsonRpcRouter;
use Tochka\JsonRpc\Facades\MiddlewareRegistry as MiddlewareRegistryFacade;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\JsonRpcResponse;
use Tochka\JsonRpc\Support\MiddlewarePipeline;
use Tochka\JsonRpc\Support\ResponseCollection;

/**
 * JsonRpcServer
 */
class JsonRpcServer
{
    private JsonRpcParserInterface $parser;
    private HandleResolverInterface $resolver;

    public function __construct(JsonRpcParserInterface $parser, HandleResolverInterface $resolver)
    {
        $this->parser = $parser;
        $this->resolver = $resolver;
    }
    
    public function handle(string $content, string $serverName = 'default', string $group = null, string $action = null): ResponseCollection
    {
        try {
            $pipeline = new MiddlewarePipeline(Container::getInstance());

            $requests = $this->parser->parse($content);

            $responses = $pipeline->send($requests)
                ->through(MiddlewareRegistryFacade::getOnceExecutedMiddleware($serverName))
                ->via('handle')
                ->then(
                    function (array $requests) use ($serverName, $group, $action) {
                        $responses = new ResponseCollection();
                        foreach ($requests as $request) {
                            $response = $this->handleRequest($request, $serverName, $group, $action);

                            if ($response !== null) {
                                $responses->add($response);
                            }
                        }

                        return $responses;
                    }
                );
        } catch (\Exception $e) {
            $responses = new ResponseCollection();
            $responses->add(JsonRpcResponse::error(ExceptionHandler::handle($e)));
        }

        return $responses;
    }

    public function handleRequest(JsonRpcRequest $request, string $serverName, string $group = null, string $action = null): ?JsonRpcResponse {
        try {
            $pipeline = new MiddlewarePipeline(Container::getInstance());
            
            $route = JsonRpcRouter::get($serverName, $request->getMethod(), $group, $action);
            
            if ($route === null) {
                throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
            }
            
            $request->setRoute($route);
            
            return $pipeline->send($request)
                ->through(MiddlewareRegistryFacade::getMiddleware($serverName))
                ->via('handle')
                ->then(
                    function (JsonRpcRequest $request) {
                        $result = $this->resolver->handle($request);

                        if ($request->getId() === null) {
                            return null;
                        }

                        return JsonRpcResponse::result($result, $request->getId());
                    }
                );
        } catch (\Exception $e) {
            return JsonRpcResponse::error(ExceptionHandler::handle($e), $request->getId());
        }
    }
}
