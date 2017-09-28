<?php

namespace Tochka\JsonRpc;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tochka\JsonRpc\Exceptions\JsonRpcHandler;

class JsonRpcServiceProvider extends ServiceProvider
{
    protected $appNew;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Необходимая вещь
        $this->app->instance('JsonRpcRequest', new JsonRpcRequest(new \StdClass, []));

        // Кастомный логгер для api
        $this->app->instance('JsonRpcLog', (new JsonRpcLogWriter())->createLogger());

        // Сервер JsonRpc
        $this->app->singleton('JsonRpcServer', function () {
            return new JsonRpcServer();
        });

        // Обработчик ошибок JsonRpc
        $this->app->singleton('JsonRpcHandler', function () {
            return new JsonRpcHandler();
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // роутинг
        $this->loadRoutes();

        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/jsonrpc.php' => config_path('jsonrpc.php')]);
    }

    protected function loadRoutes()
    {
        $routes = config('jsonrpc.routes', []);

        foreach ($routes as $route) {
            if (is_string($route)) {
                $this->route($route, ['uri' => $route]);
            } elseif (is_array($route)) {
                $uri = isset($route['uri']) ? $route['uri'] : '/';

                $this->route($uri, $route);
            }
        }
    }

    protected function route($uri, $options = [])
    {
        if (is_lumen()) {
            $app = $this->app;
            if (version_compare(getVersion(), '5.5', '>=')) {
                $app->router->post($uri, function (Request $request, JsonRpcServer $server) use ($options) {
                    return $server->handle($request, $options);
                });
            } else {
                $app->post($uri, function (Request $request, JsonRpcServer $server) use ($options) {
                    return $server->handle($request, $options);
                });
            }
        } else {
            Route::post($uri, function (Request $request, JsonRpcServer $server) use ($options) {
                return $server->handle($request, $options);
            });
        }
    }

}
