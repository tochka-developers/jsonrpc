<?php

namespace Tochka\JsonRpc;

use Illuminate\Support\ServiceProvider;
use Tochka\JsonRpc\Exceptions\JsonRpcHandler;

class JsonRpcServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Кастомный логгер для api
        $this->app->instance('JsonRpcLog', (new JsonRpcLogWriter())->createLogger());

        // Сервер JsonRpc
        $this->app->singleton('JsonRpcServer', function () {
            return new JsonRpcServer();
        });

        // Обработчик ошибок JsonRpc
        $this->app->singleton('JsonRpcHandler', function() {
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
        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/jsonrpc.php' => config_path('jsonrpc.php'),]);
    }

}
