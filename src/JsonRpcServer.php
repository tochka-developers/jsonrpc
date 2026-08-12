<?php

namespace Tochka\JsonRpc;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Contracts\JsonRpcParserInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Facades\ExceptionHandler;
use Tochka\JsonRpc\Resolvers\ControllerResolver;
use Tochka\JsonRpc\Resolvers\ParamsResolver;
use Tochka\JsonRpc\Router\Router;
use Tochka\JsonRpc\Support\JsonRpcParser;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\JsonRpcResponse;
use Tochka\JsonRpc\Support\MiddlewarePipeline;
use Tochka\JsonRpc\Support\ResponseCollection;
use Tochka\JsonRpc\Support\ServerConfig;

/**
 * JsonRpcServer
 */
class JsonRpcServer
{
    private ServerConfig $config;
    private JsonRpcParserInterface $parser;
    /** @var \Closure(JsonRpcRequest $request): ParamsResolver */
    private \Closure $resolver;
    private Router $router;
    private ControllerResolver $controllerResolver;
    
    /**
     * @param ServerConfig $config
     * @param JsonRpcParserInterface|null $parser
     * @param \Closure(JsonRpcRequest $request): ParamsResolver|null $resolver
     * @param ControllerResolver|null $controllerResolver
     * @param Router|null $router
     */
    public function __construct(
        ServerConfig $config,
        ?JsonRpcParserInterface $parser = null,
        /** @var \Closure(JsonRpcRequest $request): ParamsResolver|null */
        ?\Closure $resolver = null,
        ?ControllerResolver $controllerResolver = null,
        ?Router $router = null,
    ) {
        $this->config = $config;
        $this->parser = $parser ?: new JsonRpcParser();
        $this->resolver = $resolver ?: fn(JsonRpcRequest $request) => new ParamsResolver($request);
        $this->router = $router ?: new Router($config);
        $this->controllerResolver = $controllerResolver ?: new ControllerResolver();
    }
    
    public function handle(string $content): ResponseCollection
    {
        try {
            $pipeline = new MiddlewarePipeline(Container::getInstance());
            
            $requests = $this->parser->parse($content);
            
            $responses = $pipeline->send($requests)
                ->through($this->config->onceExecutedMiddlewares)
                ->via('handle')
                ->then(
                    function (array $requests) {
                        $responses = new ResponseCollection();
                        foreach ($requests as $request) {
                            $response = $this->handleRequest($request);
                            
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
    
    /**
     * @param JsonRpcRequest $request
     * @return JsonRpcResponse|null
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     * @throws \Throwable
     */
    public function handleRequest(JsonRpcRequest $request): ?JsonRpcResponse
    {
        try {
            $pipeline = new MiddlewarePipeline(Container::getInstance());
            
            $route = $this->router->getRoute($request->method);
            if ($route === null) {
                throw new JsonRpcException(JsonRpcException::CODE_METHOD_NOT_FOUND);
            }
            
            $request->setRoute($route);
            
            return $pipeline->send($request)
                ->through($this->config->middlewares)
                ->via('handle')
                ->then(
                    function (JsonRpcRequest $request) {
                        $controller = $this->controllerResolver->resolve($request);
                        $params = ($this->resolver)($request)->resolve(
                            $request->getRoute()->getParams(),
                            $request->params
                        );
                        $result = $controller->{$request->getRoute()->controllerMethod}(...$params);
                        
                        if ($request->id === null) {
                            return null;
                        }
                        
                        return JsonRpcResponse::result($result, $request->id);
                    }
                );
        } catch (\Exception $e) {
            return JsonRpcResponse::error(ExceptionHandler::handle($e), $request->id);
        }
    }
}
