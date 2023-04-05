<?php

namespace Tochka\JsonRpc;

use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use phpDocumentor\Reflection\DocBlockFactory as ReflectionDocBlockFactory;
use Spiral\Attributes\AnnotationReader as SpiralAnnotationReader;
use Spiral\Attributes\AttributeReader as SpiralAttributeReader;
use Spiral\Attributes\Composite\MergeReader;
use Tochka\Hydrator\Contracts\ClassDefinitionParserInterface;
use Tochka\Hydrator\Contracts\ClassDefinitionsRegistryInterface;
use Tochka\Hydrator\Contracts\ExtractorInterface;
use Tochka\Hydrator\Contracts\HydratorInterface;
use Tochka\Hydrator\Contracts\MethodDefinitionParserInterface;
use Tochka\Hydrator\Definitions\ClassDefinitionParser;
use Tochka\Hydrator\Definitions\ClassDefinitionsRegistry;
use Tochka\Hydrator\Definitions\MethodDefinitionParser;
use Tochka\Hydrator\Extractor;
use Tochka\Hydrator\Extractors\ArrayExtractor;
use Tochka\Hydrator\Extractors\BenSampoEnumExtractor;
use Tochka\Hydrator\Extractors\CarbonExtractor;
use Tochka\Hydrator\Extractors\DateTimeExtractor;
use Tochka\Hydrator\Extractors\DIExtractor;
use Tochka\Hydrator\Extractors\EnumExtractor;
use Tochka\Hydrator\Extractors\ExtractByExtractor;
use Tochka\Hydrator\Extractors\NamedObjectExtractor;
use Tochka\Hydrator\Extractors\NullExtractor;
use Tochka\Hydrator\Extractors\ObjectExtractor;
use Tochka\Hydrator\Extractors\StringExtractor;
use Tochka\Hydrator\Extractors\StrongScalarExtractor;
use Tochka\Hydrator\Extractors\UnionExtractor;
use Tochka\Hydrator\Hydrator;
use Tochka\Hydrator\Hydrators\ArrayableHydrator;
use Tochka\Hydrator\Hydrators\ArrayHydrator;
use Tochka\Hydrator\Hydrators\BenSampoEnumHydrator;
use Tochka\Hydrator\Hydrators\DateTimeHydrator;
use Tochka\Hydrator\Hydrators\EnumHydrator;
use Tochka\Hydrator\Hydrators\HydrateByHydrator;
use Tochka\Hydrator\Hydrators\NamedObjectHydrator;
use Tochka\Hydrator\Hydrators\ScalarHydrator;
use Tochka\Hydrator\Registrar;
use Tochka\JsonRpc\Console\RouteCacheCommand;
use Tochka\JsonRpc\Console\RouteClearCommand;
use Tochka\JsonRpc\Console\RouteListCommand;
use Tochka\JsonRpc\Contracts\AuthInterface;
use Tochka\JsonRpc\Contracts\ExceptionHandlerInterface;
use Tochka\JsonRpc\Contracts\HandleResolverInterface;
use Tochka\JsonRpc\Contracts\JsonRpcServerInterface;
use Tochka\JsonRpc\Contracts\MiddlewareRegistryInterface;
use Tochka\JsonRpc\Contracts\ParserInterface;
use Tochka\JsonRpc\Contracts\RouteAggregatorInterface;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\Contracts\RouterInterface;
use Tochka\JsonRpc\Contracts\ValidatorInterface;
use Tochka\JsonRpc\Route\CacheRouter;
use Tochka\JsonRpc\Route\ControllerFinder;
use Tochka\JsonRpc\Route\RouteAggregator;
use Tochka\JsonRpc\Route\Router;
use Tochka\JsonRpc\Support\Auth;
use Tochka\JsonRpc\Support\DefaultHandleResolver;
use Tochka\JsonRpc\Support\MiddlewareRegistry;
use Tochka\JsonRpc\Support\Parser;
use Tochka\JsonRpc\Support\RouteCache;
use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\JsonRpc\Support\ServersConfig;
use Tochka\JsonRpc\Support\Validator;
use Tochka\TypeParser\AttributeReader;
use Tochka\TypeParser\Contracts\AttributeReaderInterface;
use Tochka\TypeParser\Contracts\ExtendedReflectionFactoryInterface;
use Tochka\TypeParser\ExtendedReflectionFactory;
use Tochka\TypeParser\ExtendedTypeFactory;
use Tochka\TypeParser\TypeFactories\DocBlockTypeFactoryMiddleware;
use Tochka\TypeParser\TypeFactories\ReflectionTypeFactoryMiddleware;

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
        $this->registerTypeSystem();
        $this->registerHydrator();

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

    private function registerTypeSystem(): void
    {
        $this->app->singleton(AttributeReaderInterface::class, function (): AttributeReaderInterface {
            return new AttributeReader(
                new MergeReader(
                    [
                        new SpiralAnnotationReader(),
                        new SpiralAttributeReader(),
                    ]
                )
            );
        });

        $this->app->when(ExtendedTypeFactory::class)
            ->needs('$typeFactoryMiddleware')
            ->give(
                [
                    ReflectionTypeFactoryMiddleware::class,
                    DocBlockTypeFactoryMiddleware::class
                ]
            );

        $this->app->singleton(
            ExtendedReflectionFactoryInterface::class,
            function (Container $container): ExtendedReflectionFactoryInterface {
                /** @var ExtendedReflectionFactory */
                return $container->make(
                    ExtendedReflectionFactory::class,
                    ['docBlockFactory' => ReflectionDocBlockFactory::createInstance()]
                );
            }
        );
    }

    private function registerHydrator(): void
    {
        $this->app->singleton(ClassDefinitionsRegistryInterface::class, ClassDefinitionsRegistry::class);
        $this->app->singleton(ClassDefinitionParserInterface::class, ClassDefinitionParser::class);
        $this->app->singleton(MethodDefinitionParserInterface::class, MethodDefinitionParser::class);
        $this->app->singleton(ExtractorInterface::class, Extractor::class);
        $this->app->singleton(HydratorInterface::class, Hydrator::class);

        $this->app->afterResolving(ExtractorInterface::class, function (ExtractorInterface $extractor) {
            Registrar::registerDefaultExtractors($extractor);
        });

        $this->app->afterResolving(HydratorInterface::class, function (HydratorInterface $hydrator) {
            Registrar::registerDefaultHydrators($hydrator);
        });
    }
}
