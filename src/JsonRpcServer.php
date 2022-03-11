<?php

namespace Tochka\JsonRpc;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\JsonRpcParserInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Facades\ExceptionHandler;
use Tochka\JsonRpc\Facades\JsonRpcMiddlewareRepository;
use Tochka\JsonRpc\Facades\JsonRpcRouter;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\JsonRpcResponse;
use Tochka\JsonRpc\Support\ResponseCollection;

class JsonRpcServer
{
    private JsonRpcParserInterface $parser;
    private HandleResolverInterface $resolver;
    private Container $container;
    
    public function __construct(JsonRpcParserInterface $parser, HandleResolverInterface $resolver, Container $container)
    {
        $this->parser = $parser;
        $this->resolver = $resolver;
        $this->container = $container;
    }
    
    public function handle(
        ServerRequestInterface $request,
        string $serverName = 'default',
        string $group = null,
        string $action = null
    ): ResponseCollection {
        try {
            $pipeline = new Pipeline($this->container);
            
            $responses = $pipeline->send($request)
                ->through(JsonRpcMiddlewareRepository::getMiddlewareForHttpRequest($serverName))
                ->via('handleHttpRequest')
                ->then(
                    function (ServerRequestInterface $httpRequest) use ($serverName, $group, $action) {
                        $requests = $this->parser->parse($httpRequest);
                        
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
    
    public function handleRequest(
        JsonRpcRequest $request,
        string $serverName,
        string $group = null,
        string $action = null
    ): ?JsonRpcResponse {
        try {
            $pipeline = new Pipeline($this->container);
            
            $route = JsonRpcRouter::get($serverName, $request->getMethod(), $group, $action);
            
            if ($route === null) {
                throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
            }
            
            $request->setRoute($route);
            
            return $pipeline->send($request)
                ->through(JsonRpcMiddlewareRepository::getMiddlewareForJsonRpcRequest($serverName))
                ->via('handleJsonRpcRequest')
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
