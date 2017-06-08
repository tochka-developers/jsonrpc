<?php

namespace Tochka\JsonRpc;

use Monolog\Processor\WebProcessor;

/**
 * Класс доп.логирования
 *
 * Class JsonRpcProcessor
 * @package Tochka\JsonRpc
 */
class JsonRpcProcessor extends WebProcessor
{
    public function __construct()
    {
        $this->extraFields = [
            'url' => 'REQUEST_URI',
            'ip' => 'REMOTE_ADDR',
            'http_method' => 'REQUEST_METHOD',
            'server' => 'SERVER_NAME',
            'referrer' => 'HTTP_REFERER',
            'accept' => 'HTTP_ACCEPT',
            'ContentType' => 'CONTENT_TYPE'
        ];

        parent::__construct(null, null);
    }

}