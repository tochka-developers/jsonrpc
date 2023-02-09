<?php

namespace Tochka\JsonRpc\Tests\Units;

use Orchestra\Testbench\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tochka\JsonRpc\JsonRpcServiceProvider;

abstract class DefaultTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function getPackageProviders($app): array
    {
        return [JsonRpcServiceProvider::class];
    }
}
