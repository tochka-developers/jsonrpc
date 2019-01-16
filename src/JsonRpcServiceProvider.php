<?php

namespace Tochka\JsonRpc;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tochka\JsonRpc\Exceptions\JsonRpcHandler as Handler;
use Tochka\JsonRpc\Facades\JsonRpcHandler;

class JsonRpcServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Необходимая вещь
        $this->app->instance(JsonRpcRequest::class, new JsonRpcRequest(new \StdClass, []));

        // Сервер JsonRpc
        $this->app->singleton(JsonRpcServer::class, function () {
            return new JsonRpcServer();
        });

        // Обработчик ошибок JsonRpc
        $this->app->singleton(JsonRpcHandler::class, function () {
            return new Handler();
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // роутинг
        $this->loadRoutes();

        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/jsonrpc.php' => config_path('jsonrpc.php')]);
    }

    protected function loadRoutes(): void
    {
        $routes = config('jsonrpc.routes', []);

        foreach ($routes as $route) {
            if (\is_string($route)) {
                $this->route($route, ['uri' => $route]);
            } elseif (\is_array($route)) {
                $uri = $route['uri'] ?? '/';

                $this->route($uri, $route);
            }
        }
    }

    protected function route($uri, array $options = []): void
    {
        Route::post($uri, function (Request $request, JsonRpcServer $server, $endpoint = null, $action = null) use ($options) {
            if (!empty($endpoint)) {
                $options['endpoint'] = $endpoint;
            }
            if (!empty($action)) {
                $options['action'] = $action;
            }

            /** @var \Illuminate\Http\Request $request */
            return $server->handle($request, $options);
        });
    }
}
