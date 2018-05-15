<?php

namespace Tochka\JsonRpc\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Processor\MemoryUsageProcessor;
use Psr\Log\LoggerInterface;

/**
 * Class CustomizeLogger
 * @package Tochka\JsonRpc\Log
 */
class CustomizeLogger
{

    const LINE_FORMAT = "[%datetime%] %level_name%: %message% %context% %extra%\n";

    /**
     * Customize the given logger instance.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function __invoke(LoggerInterface $logger)
    {
        /** @var HandlerInterface $handler */
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(static::LINE_FORMAT, null, true, true));
        }

        $logger->pushProcessor(new JsonRpcProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
    }
}