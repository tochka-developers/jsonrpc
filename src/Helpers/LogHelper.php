<?php

namespace Tochka\JsonRpc\Helpers;

use Tochka\JsonRpc\Facades\JsonRpcLog;
use Tochka\JsonRpc\JsonRpcRequest;

/**
 * Class LogHelper
 * @package App\Helpers
 */
class LogHelper
{

    const TYPE_REQUEST = 'request';
    const TYPE_SQL = 'sql';
    const TYPE_EXCEPTION = 'exception';
    const TYPE_RESPONSE = 'response';

    /**
     * Логирование запроса
     *
     * @param $type
     * @param $source
     */
    public static function log($type, $source)
    {
        if (
            ($type === self::TYPE_SQL && !is_array($source)) ||
            (!($source instanceof \stdClass) && $type === self::TYPE_EXCEPTION) ||
            (!($source instanceof JsonRpcRequest) && in_array($type, [self::TYPE_REQUEST, self::TYPE_RESPONSE], true))
        ) {
            return;
        }

        $jsonRpcRequest = app('JsonRpcRequest');
        $hideIndices = !empty($jsonRpcRequest->controller->hideDataLog) ? $jsonRpcRequest->controller->hideDataLog : false;
        $method = !empty($jsonRpcRequest->method) ? $jsonRpcRequest->method : 'Unknown';

        switch ($type) {
            case self::TYPE_REQUEST:
                $logLevel = 'info';
                $message = 'Request';
                $context = !empty($source->call) ? (array)$source->call : [];
                break;

            case self::TYPE_RESPONSE:
                $logLevel = 'info';
                $message = sprintf('Successful request to method "%s" (id-%s) with params: ', $source->method, $source->id);
                $context = !empty($source->call) ? (array)$source->call : [];
                break;

            case self::TYPE_SQL:
                $logLevel = 'info';
                $message = 'SQL';
                $context = $source;
                if (!empty($context['params'])) {
                    $context['params'] = (array)$source['params'];
                }
                break;

            case self::TYPE_EXCEPTION:
                $logLevel = 'error';
                $message = sprintf('JsonRpcException %d: %s',
                    !empty($source->code) ? $source->code : 0,
                    !empty($source->message) ? $source->message : '');
                $context = !empty($jsonRpcRequest->call) ? (array)$jsonRpcRequest->call : [];
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

        $hideData = function (&$item, $key, $rules) {
            if (in_array($key, $rules, true)) {
                $item = '****';
            }
        };

        if ($hideDataRules && !empty($context['params'])) {
            array_walk($context['params'], $hideData, $hideDataRules);
        }

        JsonRpcLog::$logLevel($message, $context);

    }

}