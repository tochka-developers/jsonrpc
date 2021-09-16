<?php

namespace Tochka\JsonRpc\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Exceptions\RPC\InvalidParametersException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\Support\JsonRpcRequest;

/**
 * @deprecated
 */
trait JsonRpcController
{
    /** @var array */
    protected $arrayRequest;
    /** @var JsonRpcRequest */
    protected $jsonRpcRequest;
    protected $validateMessageBag;
    
    /**
     * @param JsonRpcRequest $request
     * @ApiIgnore()
     */
    public function setJsonRpcRequest(JsonRpcRequest $request): void
    {
        $this->jsonRpcRequest = $request;
    }

    /**
     * Возвращает массив с переданными в запросе параметрами
     *
     * @return array
     */
    protected function getArrayRequest(): array
    {
        if ($this->arrayRequest === null) {
            return ArrayHelper::fromObject($this->jsonRpcRequest->getParams() ?? []);
        }

        return $this->arrayRequest;
    }

    /**
     * Валидация переданных в контроллер параметров
     *
     * @param array $rules Правила валидации
     * @param array $messages Сообщения об ошибках
     * @param bool  $noException Если true - Exception генерироваться не будет
     *
     * @return bool|MessageBag Прошла валидация или нет
     * @throws InvalidParametersException
     */
    protected function validate($rules, array $messages = [], $noException = false)
    {
        return $this->validateData($this->getArrayRequest(), $rules, $messages, $noException);
    }

    /**
     * Валидация любых данных
     *
     * @param array|\StdClass $data Данные для валидации
     * @param array           $rules Правила валидации
     * @param array           $messages Сообщения об ошибках
     * @param bool            $noException Если true - Exception генерироваться не будет
     *
     * @return bool|MessageBag Прошла валидация или нет
     * @throws InvalidParametersException
     */
    protected function validateData($data, $rules, array $messages = [], $noException = false)
    {
        if (is_object($data)) {
            $data = ArrayHelper::fromObject($data);
        }

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
     *
     * @param array $rules Правила валидации
     * @param array $messages Сообщения об ошибках
     * @param bool  $noException Если true - Exception генерироваться не будет
     *
     * @return array
     * @throws InvalidParametersException
     */
    protected function validateAndFilter($rules, array $messages = [], $noException = false): array
    {
        $this->validateMessageBag = $this->validateData($this->getArrayRequest(), $rules, $messages, $noException);

        return $this->extractInputFromRules($this->getArrayRequest(), $rules);
    }

    /**
     * Get the request input based on the given validation rules.
     *
     * @param array|\stdClass $data
     * @param array           $rules
     *
     * @return array
     */
    protected function extractInputFromRules($data, array $rules): array
    {
        if (is_object($data)) {
            $data = (array) $data;
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

                if (is_array($data)) {
                    if (array_key_exists($rule, $data)) {
                        $additional[$rule][$key] = $value;
                    } elseif ($rule === '*') {
                        $arrays[$key] = $value;
                    }
                }
            } elseif (is_array($data) && array_key_exists($rule, $data)) {
                $result[$rule] = $data[$rule];
            } elseif ($rule === '*' && is_array($data)) {
                $isGlobalArray = true;
            }
        }

        if (!empty($arrays)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->extractInputFromRules($item, $arrays);
            }

            return $result;
        }

        if ($isGlobalArray) {
            return $data;
        }

        foreach ($additional as $key => $item) {
            $result[$key] = $this->extractInputFromRules($data[$key], $item);
        }

        return $result;
    }
}
