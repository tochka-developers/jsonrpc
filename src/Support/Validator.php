<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator as LaravelValidator;
use Tochka\JsonRpc\Contracts\ValidatorInterface;
use Tochka\JsonRpc\Exceptions\ValidationException;

class Validator implements ValidatorInterface
{
    public function validate(array $data, array $rules, array $messages = []): bool
    {
        $validator = $this->getValidator($data, $rules, $messages);
        return $validator->passes();
    }

    public function validateAndGetErrors(array $data, array $rules, array $messages = []): array
    {
        $validator = $this->getValidator($data, $rules, $messages);

        if (!$validator->passes()) {
            $exception = new ValidationException($validator);
            return $exception->getJsonRpcErrors($validator);
        }

        return [];
    }

    public function validateAndThrow(array $data, array $rules, array $messages = []): void
    {
        $validator = $this->getValidator($data, $rules, $messages);

        if (!$validator->passes()) {
            throw new ValidationException($validator);
        }
    }

    private function getValidator(array $data, array $rules, array $messages = []): LaravelValidator
    {
        return ValidatorFacade::make($data, $rules, $messages);
    }
}
