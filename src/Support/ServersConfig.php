<?php

namespace Tochka\JsonRpc\Support;

/**
 * @psalm-import-type ServerConfigArray from ServerConfig
 */
class ServersConfig
{
    /** @var array<string, ServerConfig> */
    public array $serversConfig = [];

    /**
     * @param array<string, ServerConfigArray> $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $serverName => $serverConfig) {
            $this->serversConfig[$serverName] = new ServerConfig($serverConfig);
        }
    }
}
