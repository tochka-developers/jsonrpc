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

    public static $hideIndices;

    /**
     * Сохранение информации по скрытию данных
     * @param array $data
     */
    public static function init(array $data)
    {
        if (null === self::$hideIndices) {
            self::$hideIndices = $data;
        }
    }

    /**
     * Логирование запроса
     *
     * @param $type
     * @param $source
     */
    public static function log($type, $source)
    {

        $hideDataRules = false;

        if (
            ($type === self::TYPE_SQL && !is_array($source)) ||
            (!($source instanceof \stdClass) && $type === self::TYPE_EXCEPTION) ||
            (!($source instanceof JsonRpcRequest) && in_array($type, [self::TYPE_REQUEST, self::TYPE_RESPONSE], true))
        ) {
            return;
        }

        switch ($type) {
            case self::TYPE_REQUEST:
                $logLevel = 'info';
                $message = 'Request';
                $context = !empty($source->call) ? (array)$source->call : [];
                $hideDataRules = !empty(self::$hideIndices[self::TYPE_REQUEST][$source->method])
                    ? self::$hideIndices[self::TYPE_REQUEST][$source->method]
                    : false;
                break;

            case self::TYPE_RESPONSE:
                $logLevel = 'info';
                $message = sprintf('Successful request to method "%s" (id-%s) with params: ', $source->method, $source->id);
                $context = !empty($source->call) ? (array)$source->call : [];
                $hideDataRules = !empty(self::$hideIndices[self::TYPE_RESPONSE][$source->method])
                    ? self::$hideIndices[self::TYPE_RESPONSE][$source->method]
                    : false;
                break;

            case self::TYPE_SQL:
                $logLevel = 'info';
                $message = 'SQL';
                $context = $source;
                if (!empty($context['params'])) {
                    $context['params'] = (array)$source['params'];
                }
                $jsonRpcRequest = app('JsonRpcRequest');
                $hideDataRules = !empty(self::$hideIndices[self::TYPE_SQL][$jsonRpcRequest->method])
                    ? self::$hideIndices[self::TYPE_SQL][$jsonRpcRequest->method]
                    : false;
                break;

            case self::TYPE_EXCEPTION:
                $logLevel = 'error';
                $message = sprintf('JsonRpcException %d: %s',
                    !empty($source->code) ? $source->code : 0,
                    !empty($source->message) ? $source->message : '');
                $jsonRpcRequest = app('JsonRpcRequest');
                $context = !empty($jsonRpcRequest->call) ? (array)$jsonRpcRequest->call : [];
                $hideDataRules = !empty(self::$hideIndices[self::TYPE_EXCEPTION][$jsonRpcRequest->method])
                    ? self::$hideIndices[self::TYPE_EXCEPTION][$jsonRpcRequest->method]
                    : false;
                break;

            default:
                $logLevel = 'info';
                $message = 'No log type specified';
                $context['params'] = [];
                break;
        }

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