<?php

namespace Tochka\JsonRpc;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tochka\JsonRpc\Exceptions\JsonRpcHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class JsonRpcServiceProvider extends ServiceProvider
{
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
        $path = storage_path(config('jsonrpc.log_path', 'logs/jsonrpc/activity.log'));
        $numOfKeepFiles = config('jsonrpc.log_max_files', 10);

        $handler = new RotatingFileHandler($path, $numOfKeepFiles, null, true, 0775);
        $logFormat = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        $handler->setFormatter(new LineFormatter($logFormat, null, true, true));

        $logger = new Monolog('JsonRpc');
        $logger->pushHandler($handler);
        $logger->pushProcessor(new JsonRpcProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());

        $this->app->instance('JsonRpcLog', $logger);

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
                $app = $app->router;
            }
            $app->post($uri,
                function (Request $request, JsonRpcServer $server, $endpoint = null, $action = null) use ($options) {
                    if (!empty($endpoint)) {
                        $options['endpoint'] = $endpoint;
                    }
                    if (!empty($action)) {
                        $options['action'] = $action;
                    }
                    return $server->handle($request, $options);
                });
        } else {
            Route::post($uri,
                function (Request $request, JsonRpcServer $server, $endpoint = null, $action = null) use ($options) {
                    if (!empty($endpoint)) {
                        $options['endpoint'] = $endpoint;
                    }
                    if (!empty($action)) {
                        $options['action'] = $action;
                    }
                    return $server->handle($request, $options);
                });
        }
    }

}
