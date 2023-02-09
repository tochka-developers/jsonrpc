<?php

namespace Tochka\JsonRpc;

use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory as ReflectionDocBlockFactory;
use Spiral\Attributes\AnnotationReader as SpiralAnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\MergeReader;
use Tochka\JsonRpc\Casters\BenSampoEnumCaster;
use Tochka\JsonRpc\Casters\CarbonCaster;
use Tochka\JsonRpc\Casters\EnumCaster;
use Tochka\JsonRpc\Console\RouteCacheCommand;
use Tochka\JsonRpc\Console\RouteClearCommand;
use Tochka\JsonRpc\Console\RouteListCommand;
use Tochka\JsonRpc\Contracts\AnnotationReaderInterface;
use Tochka\JsonRpc\Contracts\AuthInterface;
use Tochka\JsonRpc\Contracts\CasterRegistryInterface;
use Tochka\JsonRpc\Contracts\DocBlockFactoryInterface;
use Tochka\JsonRpc\Contracts\ExceptionHandlerInterface;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\JsonRpcServerInterface;
use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;
use Tochka\JsonRpc\Contracts\ParamsResolverInterface;
use Tochka\JsonRpc\Contracts\ParserInterface;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\Contracts\ValidatorInterface;
use Tochka\JsonRpc\Route\CacheParamsResolver;
use Tochka\JsonRpc\Route\CacheRouter;
use Tochka\JsonRpc\Route\ControllerFinder;
use Tochka\JsonRpc\Route\ParamsResolver;
use Tochka\JsonRpc\Route\RouteAggregator;
use Tochka\JsonRpc\Route\Router;
use Tochka\JsonRpc\Support\AnnotationReader;
use Tochka\JsonRpc\Support\Auth;
use Tochka\JsonRpc\Support\CasterRegistry;
use Tochka\JsonRpc\Support\DefaultHandleResolver;
use Tochka\JsonRpc\Support\DocBlockFactory;
use Tochka\JsonRpc\Support\MiddlewareRegistry;
use Tochka\JsonRpc\Support\Parser;
use Tochka\JsonRpc\Support\RouteCache;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\JsonRpc\Support\ServersConfig;
use Tochka\JsonRpc\Support\Validator;

/**
 * @psalm-api
 * @psalm-import-type ServerConfigArray from ServerConfig
 */
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

    public function register(): void
    {
        $this->registerIgnoredAnnotations();

        $this->app->when(ControllerFinder::class)
            ->needs('$appBasePath')
            ->give($this->app->basePath());

        $this->app->singleton(ServersConfig::class, function () {
            /** @var array<string, ServerConfigArray> $servers */
            $servers = Config::get('jsonrpc', []);

            return new ServersConfig($servers);
        });

        $this->app->singleton(ValidatorInterface::class, Validator::class);
        $this->app->singleton(ParserInterface::class, Parser::class);
        $this->app->singleton(AuthInterface::class, Auth::class);
        $this->app->singleton(RouteCacheInterface::class, function (): RouteCacheInterface {
            return new RouteCache($this->app->bootstrapPath('cache'), 'jsonrpc_routes');
        });

        $this->app->singleton(AnnotationReaderInterface::class, function (): AnnotationReaderInterface {
            return new AnnotationReader(
                new MergeReader(
                    [
                        new SpiralAnnotationReader(),
                        new AttributeReader(),
                    ]
                )
            );
        });

        $this->app->singleton(
            DocBlockFactoryInterface::class,
            function (Container $container): DocBlockFactoryInterface {
                /** @var DocBlockFactory */
                return $container->make(
                    DocBlockFactory::class,
                    ['docBlockFactory' => ReflectionDocBlockFactory::createInstance()]
                );
            }
        );

        $this->app->singleton(
            CasterRegistryInterface::class,
            function (CasterRegistry $registry): CasterRegistryInterface {
                if (class_exists('\BenSampo\Enum\Enum')) {
                    $registry->addCaster(new BenSampoEnumCaster());
                }
                if (class_exists('\Carbon\Carbon')) {
                    $registry->addCaster(new CarbonCaster());
                }
                if (function_exists('enum_exists')) {
                    $registry->addCaster(new EnumCaster());
                }

                return $registry;
            }
        );

        $this->app->singleton(ParamsResolverInterface::class, ParamsResolver::class);
        $this->app->extend(
            ParamsResolverInterface::class,
            function (ParamsResolverInterface $paramsResolver, Container $container): ParamsResolverInterface {
                /** @var CacheParamsResolver */
                return $container->make(CacheParamsResolver::class, ['paramsResolver' => $paramsResolver]);
            }
        );

        $this->app->singleton(RouterInterface::class, Router::class);
        $this->app->extend(
            RouterInterface::class,
            function (RouterInterface $router, Container $container): RouterInterface {
                /** @var CacheRouter */
                return $container->make(CacheRouter::class, ['router' => $router]);
            }
        );

        $this->app->singleton(
            MiddlewareRegistryInterface::class,
            function (ServersConfig $config, MiddlewareRegistry $registry): MiddlewareRegistryInterface {
                foreach ($config->serversConfig as $serverName => $serverConfig) {
                    $registry->addMiddlewaresFromConfig($serverName, $serverConfig);
                }

                return $registry;
            }
        );

        $this->app->singleton(
            RouteAggregatorInterface::class,
            function (ServersConfig $config, RouteAggregator $aggregator): RouteAggregatorInterface {
                foreach ($config->serversConfig as $serverName => $serverConfig) {
                    $aggregator->addServer($serverName, $serverConfig);
                }

                return $aggregator;
            }
        );

        $this->app->singleton(HandleResolverInterface::class, DefaultHandleResolver::class);

        $this->app->singleton(JsonRpcServerInterface::class, JsonRpcServer::class);
        $this->app->singleton(ExceptionHandlerInterface::class, ExceptionHandler::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    RouteCacheCommand::class,
                    RouteClearCommand::class,
                    RouteListCommand::class
                ]
            );
        }

        // публикуем конфигурации
        $this->publishes(
            [
                __DIR__ . '/../config/jsonrpc.php' => $this->app->configPath('jsonrpc.php')
            ],
            'jsonrpc-config'
        );
    }

    private function registerIgnoredAnnotations(): void
    {
        foreach (self::IGNORED_ANNOTATIONS as $annotationName) {
            DoctrineAnnotationReader::addGlobalIgnoredName($annotationName);
        }
    }
}
