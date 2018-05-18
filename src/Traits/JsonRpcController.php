<?php

namespace Tochka\JsonRpc\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Tochka\JsonRpc\Exceptions\RPC\InvalidParametersException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\JsonRpcRequest;

trait JsonRpcController
{
    protected $arrayRequest;
    protected $validateMessageBag;

    /**
     * Возвращает массив с переданными в запросе параметрами
     * @return array
     */
    protected function getArrayRequest()
    {
        if (null === $this->arrayRequest) {
            /** @var JsonRpcRequest $request */
            $request = app('JsonRpcRequest');
            return ArrayHelper::fromObject($request->call->params ?? []);
        }
        return $this->arrayRequest;
    }

    /**
     * Валидация переданных в контроллер параметров
     * @param array $rules Правила валидации
     * @param array $messages Сообщения об ошибках
     * @param bool $noException Если true - Exception генерироваться не будет
     * @return bool|MessageBag Прошла валидация или нет
     * @throws InvalidParametersException
     */
    protected function validate($rules, $messages = [], $noException = false)
    {
        return $this->validateData($this->getArrayRequest(), $rules, $messages, $noException);
    }

    /**
     * Валидация любых данных
     * @param array|\StdClass $data Данные для валидации
     * @param array $rules Правила валидации
     * @param array $messages Сообщения об ошибках
     * @param bool $noException Если true - Exception генерироваться не будет
     * @return bool|MessageBag Прошла валидация или нет
     * @throws InvalidParametersException
     */
    protected function validateData($data, $rules, $messages = [], $noException = false)
    {
        if (is_object($data)) {
            $data = ArrayHelper::fromObject($data);
        }

        /** @var Validator $validator */
        $validator = Validator::make($data, $rules, $messages);
        $validBag = $validator->errors();

        if ($validBag->any()) {
            if ($noException) {
                return $validBag;
            }

            throw new InvalidParametersException($validBag);
        }

        return true;
    }

    /**
     * Валидирует и фильтрует переданные в контроллер параметры. Возвращает отфильтрованный массив с параметрами
     * @param array $rules Правила валидации
     * @param array $messages Сообщения об ошибках
     * @param bool  $noException Если true - Exception генерироваться не будет
     *
     * @return array
     * @throws \Tochka\JsonRpc\Exceptions\RPC\InvalidParametersException
     */
    protected function validateAndFilter($rules, array $messages = [], $noException = false)
    {
        $this->validateMessageBag = $this->validateData($this->getArrayRequest(), $rules, $messages, $noException);
        return $this->extractInputFromRules($this->getArrayRequest(), $rules);
    }

    /**
     * Get the request input based on the given validation rules.
     *
     * @param  array|\stdClass  $data
     * @param  array  $rules
     * @return array
     */
    protected function extractInputFromRules($data, array $rules)
    {
        if (\is_object($data)) {
            $data = (array)$data;
        }

        $result = [];
        $additional = [];
        $arrays = [];
        $isGlobalArray = false;

        foreach ($rules as $rule => $value) {
            if (Str::contains($rule, '.')) {
                $attributes = explode('.', $rule);
                $rule = array_shift($attributes);
                $key = implode('.', $attributes);

                if (\is_array($data) && array_key_exists($rule, $data)) {
                    $additional[$rule][$key] = $value;
                } elseif ($rule === '*' and \is_array($data)) {
                    $arrays[$key] = $value;
                }
            } elseif (\is_array($data) && array_key_exists($rule, $data)) {
                $result[$rule] = $data[$rule];
            } elseif ($rule === '*' and \is_array($data)) {
                $isGlobalArray = true;
            }
        }

        if (!empty($arrays)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->extractInputFromRules($item, $arrays);
            }
            return $result;
        } elseif ($isGlobalArray) {
            return $data;
        }

        foreach ($additional as $key => $item) {
            $result[$key] = $this->extractInputFromRules($data[$key], $item);
        }

        return $result;
    }
}