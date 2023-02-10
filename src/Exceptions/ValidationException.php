<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Validation\Validator;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParametersException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParametersError;

class ValidationException extends InvalidParametersException
{
    public function __construct(Validator $validator, ?\Throwable $previous = null)
    {
        $errors = new InvalidParametersError(
            $this->getJsonRpcErrors($validator)
        );

        parent::__construct($errors, $previous);
    }

    /**
     * @param Validator $validator
     * @return array<InvalidParameterError>
     */
    public function getJsonRpcErrors(Validator $validator): array
    {
        $errors = [];

        /** @var array<string, array<string, array>> $failedRules */
        $failedRules = $validator->failed();
        $failedMessages = $validator->getMessageBag();

        foreach ($failedRules as $attributeName => $rule) {
            /** @var array<array-key, string> $attributeMessages */
            $attributeMessages = $failedMessages->get($attributeName);

            foreach ($rule as $ruleName => $_) {
                if (count($attributeMessages) === 0) {
                    $message = $ruleName;
                } else {
                    $message = array_shift($attributeMessages);
                }



                /** @psalm-suppress MixedArgument */
                $errors[] = new InvalidParameterError($attributeName, $ruleName, $message);
            }
        }

        return $errors;
    }
}
