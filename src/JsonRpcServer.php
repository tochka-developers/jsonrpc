<?php

namespace Tochka\JsonRpc;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Tochka\JsonRpc\Facades\ExceptionHandler;
use Tochka\JsonRpc\Support\JsonRpcHandleResolver;
use Tochka\JsonRpc\Support\JsonRpcParser;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\MiddlewarePipeline;
use Tochka\JsonRpc\Support\ServerConfig;

/**
 * Class JsonRpcServer
 *
 * @package Tochka\JsonRpc
 */
class JsonRpcServer
{
    /** @var JsonRpcParser */
    protected $parser;
    /** @var JsonRpcHandleResolver */
    protected $resolver;
    /** @var ServerConfig */
    protected $config;

    public function __construct(JsonRpcParser $parser, JsonRpcHandleResolver $resolver)
    {
        $this->parser = $parser;
        $this->resolver = $resolver;
    }

    /**
     * @param Request     $request
     * @param string      $serverName
     * @param string|null $group
     * @param string|null $action
     *
     * @return Response
     */
    public function handle(
        Request $request,
        string $serverName = 'default',
        string $group = null,
        string $action = null
    ): ?Response {
        try {
            $this->config = new ServerConfig(Config::get('jsonrpc.' . $serverName, []));
            $pipeline = new MiddlewarePipeline(Container::getInstance());

            $requests = $this->parser->parse($request);

            $responses = $pipeline->send($requests)
                ->through($this->config->onceExecutedMiddleware)
                ->via('handle')
                ->then(function (array $requests) use ($group, $action) {
                    $responses = [];
                    foreach ($requests as $request) {
                        $response = $this->handleRequest($request, $group, $action);

                        if (!empty($response)) {
                            $responses[] = $response;
                        }
                    }

                    return $responses;
                });
        } catch (\Exception $e) {
            $responses = [$this->resultError($e)];
        }

        if (empty($responses)) {
            return new Response('', Response::HTTP_OK);
        }

        $response = count($responses) > 1 ? $responses : (array) $responses[0];
        $json = json_encode($response, JSON_UNESCAPED_UNICODE);

        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);

    }

    public function handleRequest(JsonRpcRequest $request, string $group = null, string $action = null)
    {
        try {
            $pipeline = new MiddlewarePipeline(Container::getInstance());
            $this->resolver->resolve($request, $group, $action);

            return $pipeline->send($request)
                ->through($this->config->middleware)
                ->via('handle')
                ->then(static function (JsonRpcRequest $request) {
                    $result = $request->controller->{$request->method}(...$request->params);
                    if (empty($request->id)) {
                        return null;
                    }

                    // создаем ответ
                    $answer = (object) [];
                    $answer->jsonrpc = '2.0';
                    $answer->id = $request->id;
                    $answer->result = $result;

                    return $answer;

                });
        } catch (\Exception $e) {
            return $this->resultError($e);
        }
    }

    private function resultError(\Exception $e): \stdClass
    {
        $answer = (object) [];
        $answer->jsonrpc = '2.0';
        $answer->error = ExceptionHandler::handle($e);

        return $answer;
    }
}
