<?php

namespace Tochka\JsonRpc\Tests\Units\DTO;

use Tochka\JsonRpc\DTO\JsonRpcClient;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\DTO\JsonRpcClient
 */
class JsonRpcClientTest extends DefaultTestCase
{
    public function testGetName(): void
    {
        $expectedClient = 'testClient';

        $client = new JsonRpcClient($expectedClient);
        $actual = $client->getName();

        self::assertEquals($expectedClient, $actual);
    }
}
