<?php

namespace Tochka\JsonRpc;

use Illuminate\Log\Writer;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\MemoryUsageProcessor;

/**
 * Class ApiLogWriter
 * @package App\Logger
 */
class JsonRpcLogWriter extends Writer
{

    const LINE_FORMAT = "[%datetime%] %level_name%: %message% %context% %extra%\n";

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
    public function createLogger(): Monolog
    {
        $path = storage_path(config('jsonrpc.log_path', 'logs/jsonrpc/activity.log'));
        $numOfKeepFiles = config('jsonrpc.log_max_files', 10);

        $handler = new RotatingFileHandler($path, $numOfKeepFiles, $this->parseLevel('debug'), true, 0775);
        $handler->setFormatter(new LineFormatter(static::LINE_FORMAT, null, true, true));

        $this->log->pushHandler($handler);
        $this->log->pushProcessor(new JsonRpcProcessor());
        $this->log->pushProcessor(new MemoryUsageProcessor());

        return $this->log;
    }
}