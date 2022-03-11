<?php

namespace Tochka\JsonRpc;

use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory;
use Psr\SimpleCache\CacheInterface;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\MergeReader;
use Tochka\JsonRpc\Casters\BenSampoEnumCaster;
use Tochka\JsonRpc\Casters\CarbonCaster;
use Tochka\JsonRpc\Casters\EnumCaster;
use Tochka\JsonRpc\Console\RouteCache;
use Tochka\JsonRpc\Console\RouteClear;
use Tochka\JsonRpc\Console\RouteList;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Exceptions\ExceptionHandler;
use Tochka\JsonRpc\Facades\JsonRpcDocBlockFactory as JsonRpcDocBlockFactoryFacade;
use Tochka\JsonRpc\Helpers\ArrayFileCache;
use Tochka\JsonRpc\Route\CacheParamsResolver;
use Tochka\JsonRpc\Route\ControllerFinder;
use Tochka\JsonRpc\Route\JsonRpcCacheRouter;
use Tochka\JsonRpc\Route\JsonRpcRouteAggregator;
use Tochka\JsonRpc\Route\JsonRpcRouterResolver;
use Tochka\JsonRpc\Route\ParamsResolver;
use Tochka\JsonRpc\Support\JsonRpcDocBlockFactory;
use Tochka\JsonRpc\Support\JsonRpcHandleResolver;
use Tochka\JsonRpc\Support\JsonRpcParser;
use Tochka\JsonRpc\Support\JsonRpcRequestCast;
use Tochka\JsonRpc\Support\JsonRpcValidator;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\JsonRpcSupport\Middleware\MiddlewareRepository;

class JsonRpcServiceProvider extends ServiceProvider
{
    private const IGNORED_ANNOTATIONS = [
        'apiGroupName',
        'apiIgnoreMethod',
        'apiName',
        'apiDescription',
        'apiNote',
        'apiWarning',
        'apiParam',
        'apiRequestExample',
        'apiResponseExample',
        'apiReturn',
        'apiTag',
        'apiEnum',
        'apiObject',
        'mixin',
    ];
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('JsonRpcRouteCache', function () {
            return new ArrayFileCache('jsonrpc_routes');
        });
        
        $this->registerIgnoredAnnotations();
        
        $annotationReader = new MergeReader(
            [
                new AnnotationReader(),
                new AttributeReader(),
            ]
        );
        
        $this->app->singleton(JsonRpcDocBlockFactoryFacade::class, function() use ($annotationReader) {
            $docBlockFactory = DocBlockFactory::createInstance();
            return new JsonRpcDocBlockFactory($annotationReader, $docBlockFactory);
        });
        
        $this->app->singleton(ParamsResolverInterface::class, function () {
            $paramsResolver = new ParamsResolver();
            /** @var CacheInterface $cache */
            $cache = $this->app->make('JsonRpcRouteCache');
            return new CacheParamsResolver($paramsResolver, $cache);
        });
        
        $this->app->singleton(
            Facades\JsonRpcRouter::class,
            function () {
                $routerResolver = new JsonRpcRouterResolver();
                /** @var CacheInterface $cache */
                $cache = $this->app->make('JsonRpcRouteCache');
                return new JsonRpcCacheRouter($routerResolver, $cache);
            }
        );
        
        $this->app->singleton(Facades\JsonRpcMiddlewareRepository::class, function () {
            $manager = new MiddlewareRepository(Container::getInstance());
            
            $servers = Config::get('jsonrpc', []);
            foreach ($servers as $serverName => $serverConfig) {
                $manager->parseMiddleware($serverName, $serverConfig['middleware'] ?? []);
            }
            
            return $manager;
        });
        
        $this->app->singleton(
            Facades\JsonRpcRouteAggregator::class,
            function () use ($annotationReader) {
                $controllerFinder = new ControllerFinder();
                
                $aggregator = new JsonRpcRouteAggregator($controllerFinder, $annotationReader);
                $servers = Config::get('jsonrpc', []);
                foreach ($servers as $serverName => $serverConfig) {
                    $aggregator->addServer($serverName, new ServerConfig($serverConfig));
                }
                
                return $aggregator;
            }
        );
        
        $this->app->singleton(
            Facades\JsonRpcRequestCast::class,
            function () {
                $instance = new JsonRpcRequestCast();
                if (class_exists('\BenSampo\Enum\Enum')) {
                    $instance->addCaster(new BenSampoEnumCaster());
                }
                if (class_exists('\Carbon\Carbon')) {
                    $instance->addCaster(new CarbonCaster());
                }
                if (function_exists('enum_exists')) {
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
                
                return new JsonRpcServer($parser, $resolver, Container::getInstance());
            }
        );
        
        // Обработчик ошибок JsonRpc
        $this->app->singleton(
            Facades\ExceptionHandler::class,
            static function () {
                return new ExceptionHandler();
            }
        );
        
        $this->app->singleton(\Tochka\JsonRpc\Facades\JsonRpcValidator::class, function (Validator $validator) {
            return new JsonRpcValidator($validator);
        });
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
    
    private function registerIgnoredAnnotations(): void
    {
        foreach (self::IGNORED_ANNOTATIONS as $annotationName) {
            DoctrineAnnotationReader::addGlobalIgnoredName($annotationName);
        }
    }
}
