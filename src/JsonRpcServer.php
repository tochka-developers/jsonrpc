<?php

namespace Tochka\JsonRpc;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Tochka\JsonRpc\Facades\JsonRpcHandler;
use Tochka\JsonRpc\Handlers\AuthHandler;
use Tochka\JsonRpc\Handlers\BaseHandler;
use Tochka\JsonRpc\Handlers\DescriptionSmdHandler;
use Tochka\JsonRpc\Handlers\ExecuteRequestHandler;
use Tochka\JsonRpc\Handlers\ParseAndValidateHandler;
use Tochka\JsonRpc\Middleware\MethodClosureMiddleware;

/**
 * Class JsonRpcServer
 *
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
     * @param array   $options
     *
     * @return Response
     */
    public function handle(Request $request, $options = [])
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
        $this->namespace = $options['namespace'] ?? Config::get('jsonrpc.controllerNamespace',
                'App\\Http\\Controllers\\Api\\');
        $this->postfix = $options['postfix'] ?? Config::get('jsonrpc.controllerPostfix', 'Controller');
        $this->description = $options['description'] ?? Config::get('jsonrpc.description', 'JsonRpc Server');
        $this->controller = $options['controller'] ?? Config::get('jsonrpc.defaultController', 'Api');
        $this->middleware = $options['middleware'] ?? Config::get('jsonrpc.middleware',
                [MethodClosureMiddleware::class]);
        $this->acl = $options['acl'] ?? Config::get('jsonrpc.acl', []);
        $this->auth = $options['auth'] ?? Config::get('jsonrpc.authValidate', true);
        $this->handlers = $options['handlers'] ?? Config::get('jsonrpc.handlers', self::DEFAULT_HANDLERS);
        $this->endpoint = $options['endpoint'] ?? null;
        $this->action = $options['action'] ?? null;
    }
}
