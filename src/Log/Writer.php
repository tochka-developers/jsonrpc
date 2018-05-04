<?php

namespace Tochka\JsonRpc\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\MemoryUsageProcessor;

/**
 * Class ApiLogWriter
 * @package App\Logger
 */
class Writer extends \Illuminate\Log\Writer
{

    public $log;

    public function __construct()
    {
        $this->log = new Monolog('JsonRpc');
        parent::__construct($this->log);
    }

    /**
     * Creator for convenient reuse of some Writer functions
     * @return Monolog
     */
    public function createLogger()
    {
        $path = config('jsonrpc.logging_channel.path', storage_path('logs/jsonrpc/activity.log'));
        $numOfKeepFiles = config('jsonrpc.logging_channel.days', 10);

        $handler = new RotatingFileHandler($path, $numOfKeepFiles, $this->parseLevel('debug'), true, 0775);
        $handler->setFormatter(new LineFormatter(CustomizeLogger::LINE_FORMAT, null, true, true));

        $this->log->pushHandler($handler);
        $this->log->pushProcessor(new JsonRpcProcessor());
        $this->log->pushProcessor(new MemoryUsageProcessor());

        return $this->log;
    }
}