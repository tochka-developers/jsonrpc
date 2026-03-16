<?php

namespace Tochka\JsonRpc;

use Illuminate\Support\ServiceProvider;
use Tochka\JsonRpc\Console\RouteCache;
use Tochka\JsonRpc\Console\RouteClear;
use Tochka\JsonRpc\Console\RouteList;
use Tochka\JsonRpc\Exceptions\ExceptionHandler;

class JsonRpcServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Обработчик ошибок JsonRpc
        $this->app->singleton(
            Facades\ExceptionHandler::class,
            static function () {
                return new ExceptionHandler();
            }
        );
    }
    
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RouteCache::class, RouteClear::class, RouteList::class]);
        }
        
        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/jsonrpc.php' => config_path('jsonrpc.php')], 'jsonrpc-config');
    }
}
