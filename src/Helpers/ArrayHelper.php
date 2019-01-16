<?php

namespace Tochka\JsonRpc\Helpers;

/**
 * Хелпер работы с массивами
 *
 * Class ArrayHelper
 * @package App\Helpers
 */
class ArrayHelper
{
    /**
     * Рекурсивно переводит объект в массив
     * @param mixed $object
     * @return mixed
     */
    public static function fromObject($object)
    {
        if (\is_object($object)) {
            $object = (array)$object;
        }
        if (\is_array($object)) {
            $array = [];
            foreach ($object as $key => $value) {
                $array[$key] = self::fromObject($value);
            }
        } else {
            $array = $object;
        }
        return $array;
    }
}