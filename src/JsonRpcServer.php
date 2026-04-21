<?php

namespace Tochka\JsonRpc;

use Illuminate\Container\Container;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\JsonRpc\Contracts\JsonRpcParserInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Facades\ExceptionHandler;
use Tochka\JsonRpc\Resolvers\JsonRpcParamsResolver;
use Tochka\JsonRpc\Resolvers\Handlers\DIResolver;
use Tochka\JsonRpc\Resolvers\Handlers\EnumResolver;
use Tochka\JsonRpc\Resolvers\Handlers\MixedResolver;
use Tochka\JsonRpc\Resolvers\Handlers\ObjectResolver;
use Tochka\JsonRpc\Resolvers\Handlers\ParamsResolver;
use Tochka\JsonRpc\Resolvers\Handlers\PrimitiveResolver;
use Tochka\JsonRpc\Resolvers\ParamsResolverInterface;
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
    private ParamsResolverInterface $resolver;
    private Router $router;
    
    /**
     * @throws JsonRpcException
     */
    public function __construct(
        ServerConfig $config,
        ?JsonRpcParserInterface $parser = null,
        ?ParamsResolverInterface $resolver = null,
        ?Router $router = null,
    ) {
        $this->config = $config;
        $this->parser = $parser ?: new JsonRpcParser();
        $this->resolver = $resolver ?: new JsonRpcParamsResolver(
            [
                DIResolver::class,
                EnumResolver::class,
                MixedResolver::class,
                ObjectResolver::class,
                PrimitiveResolver::class,
                ParamsResolver::class,
            ],
            $config->customCasters,
        );
        $this->router = $router ?: new Router($config);
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
     * @throws InvalidArgumentException
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
                        $result = $this->resolver->handle($request);
                        
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
