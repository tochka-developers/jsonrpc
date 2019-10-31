<?php

namespace Tochka\JsonRpc\Helpers;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tochka\JsonRpc\JsonRpcRequest;

/**
 * Class LogHelper
 *
 * @package App\Helpers
 */
class LogHelper
{
    public const TYPE_REQUEST = 'request';
    public const TYPE_SQL = 'sql';
    public const TYPE_EXCEPTION = 'exception';
    public const TYPE_RESPONSE = 'response';

    /**
     * Логирование запроса
     *
     * @param $type
     * @param $source
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function log($type, $source): void
    {
        if (
            (!($source instanceof \stdClass) && $type === self::TYPE_EXCEPTION) ||
            (!($source instanceof JsonRpcRequest) && \in_array($type, [self::TYPE_REQUEST, self::TYPE_RESPONSE], true))
        ) {
            return;
        }

        /** @var JsonRpcRequest $jsonRpcRequest */
        $jsonRpcRequest = Container::getInstance()->make(JsonRpcRequest::class);
        $hideIndices = !empty($jsonRpcRequest->controller->hideDataLog) ? $jsonRpcRequest->controller->hideDataLog : false;
        $method = !empty($jsonRpcRequest->method) ? $jsonRpcRequest->method : 'Unknown';

        switch ($type) {
            case self::TYPE_REQUEST:
                $logLevel = 'info';
                $message = 'Request';
                $context = !empty($source->call) ? (array) $source->call : [];
                break;

            case self::TYPE_RESPONSE:
                $logLevel = 'info';
                $message = sprintf('Successful request to method "%s" (id-%s) with params: ', $source->method,
                    $source->id);
                $context = !empty($source->call) ? (array) $source->call : [];
                break;

            case self::TYPE_EXCEPTION:
                $logLevel = 'error';
                $message = sprintf('JsonRpcException %d: %s',
                    isset($source->code) ? $source->code : 0,
                    !empty($source->message) ? $source->message : '');
                $context = !empty($jsonRpcRequest->call) ? (array) $jsonRpcRequest->call : [];
                break;

            default:
                $logLevel = 'info';
                $message = 'No log type specified';
                $context['params'] = [];
                break;
        }

        $hideDataRules = !empty($hideIndices[$type][$method])
            ? $hideIndices[$type][$method]
            : false;

        $hideData = static function (&$item, $key, $rules) {
            if (\in_array($key, $rules, true)) {
                $item = '****';
            }
        };

        if ($hideDataRules && !empty($context['params'])) {
            array_walk($context['params'], $hideData, $hideDataRules);
        }

        Log::channel(Config::get('jsonrpc.log_channel'))
            ->$logLevel($message, $context);

    }

}