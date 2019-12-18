<?php

namespace Tochka\JsonRpc;

use Illuminate\Support\ServiceProvider;
use Tochka\JsonRpc\Exceptions\ExceptionHandler;
use Tochka\JsonRpc\Support\JsonRpcHandleResolver;
use Tochka\JsonRpc\Support\JsonRpcParser;

class JsonRpcServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(\Tochka\JsonRpc\Facades\JsonRpcServer::class, static function () {
            $parser = new JsonRpcParser();
            $resolver = new JsonRpcHandleResolver();

            return new JsonRpcServer($parser, $resolver);
        });

        // Обработчик ошибок JsonRpc
        $this->app->singleton(\Tochka\JsonRpc\Facades\ExceptionHandler::class, static function () {
            return new ExceptionHandler();
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/jsonrpc.php' => config_path('jsonrpc.php')], 'jsonrpc-config');
    }
}
