<?php

namespace Tochka\JsonRpc\Helpers;

/**
 * Хелпер для работы с логами
 */
class LogHelper
{
    public static function hidePrivateData($data, array $rules): array
    {
        foreach ($rules as $rule) {
            $rule = explode('.', $rule);

            $data = self::hideDataByRule($data, $rule);
        }

        return $data;
    }

    protected static function hideDataByRule($data, array $rule): array
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return $data;
        }

        $key = array_shift($rule);

        if ($key === '*') {
            foreach ($data as $key => $value) {
                $data[$key] = self::hideDataByRule($data[$key], $rule);
            }
        } else {
            if (isset($data[$key])) {
                if (count($rule)) {
                    $data[$key] = self::hideDataByRule($data[$key], $rule);
                } else {
                    $data[$key] = '***';
                }
            }
        }

        return $data;
    }
}
