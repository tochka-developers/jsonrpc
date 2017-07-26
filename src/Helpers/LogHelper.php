<?php

namespace Tochka\JsonRpc\Helpers;

use Illuminate\Http\Request;
use Tochka\JsonRpc\Facades\JsonRpcLog;
use Tochka\JsonRpc\JsonRpcRequest;

/**
 * Class LogHelper
 * @package App\Helpers
 */
class LogHelper
{

    const REQUEST_TYPE = 'request';
    const SQL_TYPE = 'sql';
    const EXCEPTION_TYPE = 'exception';
    const RESPONSE_TYPE = 'response';

    public static $hideIndices;

    /**
     * Сохранение информации по скрыти. данных
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

        if (
            ($type === self::SQL_TYPE && !is_array($source)) ||
            (!($source instanceof \stdClass) && $type === self::EXCEPTION_TYPE) ||
            (!($source instanceof JsonRpcRequest) && in_array($type, [self::REQUEST_TYPE, self::RESPONSE_TYPE], true))
        ) {
            return;
        }

        $hideData = function (&$item, $key, $rules) {
            if (in_array($key, $rules, true)) {
                $item = '****';
            }
        };

        switch ($type) {
            case self::REQUEST_TYPE:
                $message = 'Request';
                $context = !empty($source->call) ? (array)$source->call : [];
                $logLevel = 'info';
                $hideDataRules = !empty(self::$hideIndices[self::REQUEST_TYPE][$source->method])
                    ? self::$hideIndices[self::REQUEST_TYPE][$source->method]
                    : false;
                if ($hideDataRules && !empty($context['params'])) {
                    array_walk($context['params'], $hideData, $hideDataRules);
                }
                break;

            case self::RESPONSE_TYPE:
                $message = sprintf('Successful request to method "%s" (id-%s) with params: ', $source->method, $source->id);
                $context = !empty($source->call) ? (array)$source->call : [];
                $logLevel = 'info';
                $hideDataRules = !empty(self::$hideIndices[self::RESPONSE_TYPE][$source->method])
                    ? self::$hideIndices[self::RESPONSE_TYPE][$source->method]
                    : false;
                if ($hideDataRules && !empty($context['params'])) {
                    array_walk($context['params'], $hideData, $hideDataRules);
                }
                break;

            case self::SQL_TYPE:
                $message = 'SQL';
                $context = !empty($source['params']) ? (array)$source['params'] : false;

                $method = app(Request::class)->getContent();
                $method = json_decode($method, 1);
                $method = explode('_', $method['method']);
                $method = array_pop($method);

                $hideDataRules = !empty(self::$hideIndices[self::SQL_TYPE][$method])
                    ? self::$hideIndices[self::SQL_TYPE][$method]
                    : false;

                if ($hideDataRules && $context) {
                    array_walk($context, $hideData, $hideDataRules);
                }

                $logLevel = 'info';
                break;

            case self::EXCEPTION_TYPE:
                $message = sprintf('JsonRpcException %d: %s',
                    !empty($source->code) ? $source->code : 0,
                    !empty($source->message) ? $source->message : '');

                $content = app(Request::class)->getContent();
                $context = json_decode($content, 1);
                $method = explode('_', $context['method']);
                $method = array_pop($method);

                $hideDataRules = !empty(self::$hideIndices[self::EXCEPTION_TYPE][$method])
                    ? self::$hideIndices[self::EXCEPTION_TYPE][$method]
                    : false;

                if ($hideDataRules && !empty($context['params'])) {
                    array_walk($context['params'], $hideData, $hideDataRules);
                }

                $logLevel = 'error';
                break;

            default:
                $message = 'No log type specified';
                $context = [];
                $logLevel = 'info';
                break;
        }

        JsonRpcLog::$logLevel($message, $context);

    }

}