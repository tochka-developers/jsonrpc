<?php

namespace Tochka\JsonRpc;

use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcHandler;
use Tochka\JsonRpc\Log\Writer as JsonRpcLogWriter;

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

        $this->registerLogger();

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

    /**
     * Register a custom logger instance for the api.
     */
    protected function registerLogger()
    {
        if (version_compare($this->getAppVersion(), '5.6', '>=')) {
            /** @var LogManager $logManager */
            $logManager = $this->app->make(LoggerInterface::class);

            $channelConfig = $this->app['config']->get('jsonrpc.logging_channel', [
                'name' => 'JsonRpc',
                'tap' => [\Tochka\JsonRpc\Log\CustomizeLogger::class],
                'driver' => 'daily',
                'level' => 'debug',
                'path' => \storage_path('logs/jsonrpc/activity.log'),
                'days' => 10,
            ]);

            $this->app['config']->set('logging.channels.jsonrpc', $channelConfig);

            $this->app->instance('JsonRpcLog', $logManager->channel('jsonrpc'));
            return;
        }

        $this->app->instance('JsonRpcLog', (new JsonRpcLogWriter())->createLogger());
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

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    protected function getAppVersion()
    {
        $version = $this->app->version();
        if (substr($version, 0, 7) === 'Lumen (') {
            $version = array_first(explode(')', str_replace('Lumen (', '', $version)));
        }
        return $version;
    }
}
