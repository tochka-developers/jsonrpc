<?php

namespace Tochka\JsonRpc;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Tochka\JsonRpc\Casters\EnumCaster;
use Tochka\JsonRpc\Exceptions\ExceptionHandler;
use Tochka\JsonRpc\Support\JsonRpcHandleResolver;
use Tochka\JsonRpc\Support\JsonRpcParser;
use Tochka\JsonRpc\Support\JsonRpcRequestCast;

class JsonRpcServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(
            Facades\JsonRpcRequestCast::class,
            function () {
                $reader = new CachedReader(
                    new AnnotationReader(),
                    new PhpFileCache($this->app->bootstrapPath('cache/annotations'), '.annotations.php'),
                    Config::get('app.debug')
                );

                $instance = new JsonRpcRequestCast($reader);
                if (class_exists('\BenSampo\Enum\Enum')) {
                    $instance->addCaster(new EnumCaster());
                }

                return $instance;
            }
        );

        $this->app->singleton(
            \Tochka\JsonRpc\Facades\JsonRpcServer::class,
            static function () {
                $parser = new JsonRpcParser();
                $resolver = new JsonRpcHandleResolver();

                return new JsonRpcServer($parser, $resolver);
            }
        );

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
        // публикуем конфигурации
        $this->publishes([__DIR__ . '/../config/jsonrpc.php' => config_path('jsonrpc.php')], 'jsonrpc-config');
    }
}
