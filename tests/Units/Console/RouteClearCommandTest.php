<?php

namespace Tochka\JsonRpc\Tests\Units\Console;

use Tochka\JsonRpc\Console\RouteClearCommand;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Console\RouteClearCommand
 */
class RouteClearCommandTest extends DefaultTestCase
{
    public function testHandle(): void
    {
        $cache = \Mockery::mock(RouteCacheInterface::class);
        $cache->shouldReceive('clear')
            ->once()
            ->with();

        $command = \Mockery::mock(RouteClearCommand::class)->makePartial();
        $command->shouldReceive('info');

        $command->handle($cache);
    }
}
