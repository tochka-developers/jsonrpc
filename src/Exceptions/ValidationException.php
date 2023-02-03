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

        foreach ($failedRules as $attributeName => $rule) {
            foreach ($rule as $ruleName => $parameters) {
                /** @psalm-suppress MixedAssignment */
                $message = $validator->getMessage($attributeName, $ruleName);

                /** @psalm-suppress MixedArgument */
                $errors[] = new InvalidParameterError(
                    $attributeName,
                    $ruleName,
                    $validator->makeReplacements(
                        $message,
                        $attributeName,
                        $ruleName,
                        $parameters
                    )
                );
            }
        }

        return $errors;
    }
}
