<?php

namespace Tochka\JsonRpc;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\JsonRpcParserInterface;
use Tochka\JsonRpc\Facades\ExceptionHandler;
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
    protected JsonRpcParserInterface $parser;
    protected HandleResolverInterface $resolver;
    protected ServerConfig $config;

    public function __construct(JsonRpcParserInterface $parser, HandleResolverInterface $resolver)
    {
        $this->parser = $parser;
        $this->resolver = $resolver;
    }

    /**
     * Обработка запроса
     *
     * @param string      $content
     * @param string      $serverName
     * @param string|null $group
     * @param string|null $action
     *
     * @return ResponseCollection
     */
    public function handle(
        string $content,
        string $serverName = 'default',
        string $group = null,
        string $action = null
    ): ResponseCollection {
        try {
            $this->config = new ServerConfig(Config::get('jsonrpc.' . $serverName, []));
            $pipeline = new MiddlewarePipeline(Container::getInstance());

            $requests = $this->parser->parse($content);

            $responses = $pipeline->send($requests)
                ->through($this->config->onceExecutedMiddleware)
                ->via('handle')
                ->then(
                    function (array $requests) use ($group, $action) {
                        $responses = new ResponseCollection();
                        foreach ($requests as $request) {
                            $response = $this->handleRequest($request, $group, $action);

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
        string $group = null,
        string $action = null
    ): ?JsonRpcResponse {
        try {
            $pipeline = new MiddlewarePipeline(Container::getInstance());

            $this->resolver->resolve($request, $this->config, $group, $action);

            return $pipeline->send($request)
                ->through($this->config->middleware)
                ->via('handle')
                ->then(
                    static function (JsonRpcRequest $request) {
                        $result = $request->controller->{$request->method}(...$request->params);

                        if (empty($request->id)) {
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
