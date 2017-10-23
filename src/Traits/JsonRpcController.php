<?php

namespace Tochka\JsonRpc\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Tochka\JsonRpc\Exceptions\RPC\InvalidParametersException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\JsonRpcRequest;

trait JsonRpcController
{
    protected $arrayRequest;

    /**
     * Возвращает массив с переданными в запросе параметрами
     * @return array
     */
    protected function getArrayRequest()
    {
        if (null === $this->arrayRequest) {
            /** @var JsonRpcRequest $request */
            $request = app('JsonRpcRequest');
            return ArrayHelper::fromObject($request->call->params);
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
            } else {
                throw new InvalidParametersException($validBag);
            }
        }

        return true;
    }
}