<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Tochka\JsonRpc\DTO\JsonRpcClient;
use Tochka\JsonRpc\Support\Auth;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\Auth
 */
class AuthTest extends DefaultTestCase
{
    public function testSetClient(): void
    {
        $expectedClient = new JsonRpcClient('test');

        $auth = new Auth();

        $auth->setClient($expectedClient);

        self::assertEquals($expectedClient, $auth->getClient());
    }

    public function test__construct(): void
    {
        $auth = new Auth();

        self::assertEquals(JsonRpcClient::GUEST, $auth->getClient()->getName());
    }
}
