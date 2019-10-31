<?php

namespace Tochka\JsonRpc;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tochka\JsonRpc\Facades\JsonRpcHandler;
use Tochka\JsonRpc\Handlers\AuthHandler;
use Tochka\JsonRpc\Handlers\BaseHandler;
use Tochka\JsonRpc\Handlers\DescriptionSmdHandler;
use Tochka\JsonRpc\Handlers\ExecuteRequestHandler;
use Tochka\JsonRpc\Handlers\ParseAndValidateHandler;
use Tochka\JsonRpc\Middleware\MethodClosureMiddleware;

/**
 * Class JsonRpcServer
 * @package Tochka\JsonRpc
 */
class JsonRpcServer
{
    protected const DEFAULT_HANDLERS = [
        DescriptionSmdHandler::class,
        AuthHandler::class,
        ParseAndValidateHandler::class,
        ExecuteRequestHandler::class,
    ];

    public $uri;
    public $namespace;
    public $postfix;
    public $description;
    public $controller;
    public $middleware;
    public $acl;
    public $auth;
    public $handlers;
    public $data = [];
    public $serviceName = 'guest';
    public $endpoint;
    public $action;

    protected $response;

    /**
     * @param Request $request
     * @param array $options
     *
     * @return array
     */
    public function handle(Request $request, $options = []): array
    {
        $this->fillOptions($options);

        try {
            foreach ($this->handlers as $handlerName) {
                /** @var BaseHandler $handler */
                $handler = new $handlerName();
                if (!$handler->handle($request, $this)) {
                    break;
                }
            }
        } catch (\Exception $e) {
            $answer = new \StdClass();
            $answer->jsonrpc = '2.0';
            $answer->error = JsonRpcHandler::handle($e);
            $this->response[] = $answer;
        }
        $content = \count($this->response) > 1 ? $this->response : (array)$this->response[0];

        return new Response(json_encode($content, JSON_UNESCAPED_UNICODE), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    public function setResponse($response): void
    {
        $this->response = $response;
    }

    /**
     * Заполняет параметры
     *
     * @param array $options
     */
    protected function fillOptions($options): void
    {
        $this->uri = $options['uri'] ?? '/';
        $this->namespace = $options['namespace'] ?? config('jsonrpc.controllerNamespace', 'App\\Http\\Controllers\\Api\\');
        $this->postfix = $options['postfix'] ?? config('jsonrpc.controllerPostfix', 'Controller');
        $this->description = $options['description'] ?? config('jsonrpc.description', 'JsonRpc Server');
        $this->controller = $options['controller'] ?? config('jsonrpc.defaultController', 'Api');
        $this->middleware = $options['middleware'] ?? config('jsonrpc.middleware', [MethodClosureMiddleware::class]);
        $this->acl = $options['acl'] ?? config('jsonrpc.acl', []);
        $this->auth = $options['auth'] ?? config('jsonrpc.authValidate', true);
        $this->handlers = $options['handlers'] ?? config('jsonrpc.handlers', self::DEFAULT_HANDLERS);
        $this->endpoint = $options['endpoint'] ?? null;
        $this->action = $options['action'] ?? null;
    }
}