<?php

namespace Tochka\JsonRpc\Exceptions;

use Illuminate\Validation\Concerns\FormatsMessages;

class JsonRpcValidationErrorException extends JsonRpcInvalidParametersException
{
    use FormatsMessages {
        getMessage as private getFormatMessage;
    }
    
    public function __construct(array $failedRules, ?\Throwable $previous = null)
    {
        $errors = [];
        
        foreach ($failedRules as $fieldName => $rules) {
            foreach ($rules as $ruleName => $parameters) {
                $errors[] = new JsonRpcInvalidParameterError($ruleName, $fieldName, $this->makeReplacements(
                    $this->getFormatMessage($fieldName, $ruleName), $fieldName, $ruleName, $parameters
                ));
            }
        }
        
        parent::__construct($errors, $previous);
    }
}
