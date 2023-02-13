<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Tochka\JsonRpc\Support\ServerConfig;
use Tochka\JsonRpc\Support\ServersConfig;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\ServersConfig
 */
class ServersConfigTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $fooServerConfig = ['description' => 'Foo server'];
        $barServerConfig = ['description' => 'Bar server'];
        $config = [
            'fooServer' => $fooServerConfig,
            'barServer' => $barServerConfig,
        ];
        $serversConfig = new ServersConfig($config);

        $expected = [
            'fooServer' => new ServerConfig($fooServerConfig),
            'barServer' => new ServerConfig($barServerConfig),
        ];

        self::assertEquals($expected, $serversConfig->serversConfig);
    }
}
