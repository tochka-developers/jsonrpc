<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Tochka\JsonRpc\Exceptions\RPC\InvalidParametersException;
use Tochka\JsonRpc\Helpers\ArrayHelper;

trait RequestValidation
{
    protected MessageBag $errors;
    protected array $validated = [];
    protected array $failed = [];

    /**
     * @throws InvalidParametersException
     */
    public function validate(array $rules, array $messages = [], array $attributes = []): array
    {
        return $this->runValidation($rules, $messages, $attributes);
    }

    /**
     * @throws InvalidParametersException
     */
    public function validateSilent(array $rules, array $messages = [], array $attributes = []): array
    {
        return $this->runValidation($rules, $messages, $attributes, true);
    }

    public function getValidationErrors(): MessageBag
    {
        return $this->errors ??= new MessageBag();
    }

    public function getValidationFailed(): array
    {
        return $this->failed;
    }

    /**
     * @throws InvalidParametersException
     */
    protected function runValidation(
        array $rules,
        array $messages,
        array $attributes,
        bool $suppressExceptions = false
    ): array {
        $validator = Validator::make(ArrayHelper::fromObject($this->params), $rules, $messages, $attributes);

        $this->errors = $validator->errors();
        $this->failed = $validator->failed();

        if ($this->errors->isNotEmpty() && !$suppressExceptions) {
            throw new InvalidParametersException($this->errors);
        }

        if ($this->errors->isEmpty()) {
            $this->validated = $validator->validated();
        }

        return $this->validated;
    }
}
