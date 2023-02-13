<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\ServerConfig
 */
class ServerConfigTest extends DefaultTestCase
{
    public function test__constructFull(): void
    {
        $expectedSummary = 'Test summary';
        $expectedDescription = 'Test description';
        $expectedNamespace = 'Test namespace';
        $expectedControllerSuffix = 'Test controller suffix';
        $expectedMethodDelimiter = 'Test method delimieter';
        $expectedEndpoint = 'Test endpoint';
        $expectedDynamicEndpoint = 'Test dynemic endpoint';
        $expectedMiddleware = ['one', 'two'];

        $config = [
            'summary' => $expectedSummary,
            'description' => $expectedDescription,
            'namespace' => $expectedNamespace,
            'controllerSuffix' => $expectedControllerSuffix,
            'methodDelimiter' => $expectedMethodDelimiter,
            'endpoint' => $expectedEndpoint,
            'dynamicEndpoint' => $expectedDynamicEndpoint,
            'middleware' => $expectedMiddleware,
        ];

        $serverConfig = new ServerConfig($config);

        self::assertEquals($expectedSummary, $serverConfig->summary);
        self::assertEquals($expectedDescription, $serverConfig->description);
        self::assertEquals($expectedNamespace, $serverConfig->namespace);
        self::assertEquals($expectedControllerSuffix, $serverConfig->controllerSuffix);
        self::assertEquals($expectedMethodDelimiter, $serverConfig->methodDelimiter);
        self::assertEquals($expectedEndpoint, $serverConfig->endpoint);
        self::assertEquals($expectedDynamicEndpoint, $serverConfig->dynamicEndpoint);
        self::assertEquals($expectedMiddleware, $serverConfig->middleware);
    }

    public function test__constructDefault(): void
    {
        $serverConfig = new ServerConfig([]);

        self::assertEquals('JsonRpc Server', $serverConfig->summary);
        self::assertEquals('JsonRpc Server', $serverConfig->description);
        self::assertEquals('App\Http\Controllers', $serverConfig->namespace);
        self::assertEquals('Controller', $serverConfig->controllerSuffix);
        self::assertEquals('_', $serverConfig->methodDelimiter);
        self::assertEquals('/api/v1/public/jsonrpc', $serverConfig->endpoint);
        self::assertEquals(ServerConfig::DYNAMIC_ENDPOINT_NONE, $serverConfig->dynamicEndpoint);
        self::assertEquals([], $serverConfig->middleware);
    }
}
