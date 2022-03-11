<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Validation\Concerns\FormatsMessages;
use Illuminate\Validation\Validator;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterError;

class JsonRpcValidator
{
    use FormatsMessages;
    
    private Validator $validator;
    protected $translator;
    
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
        $this->translator = $validator->getTranslator();
    }
    
    public function validate(): bool
    {
    
    }
    
    /**
     * @return array<JsonRpcInvalidParameterError>
     */
    public function getJsonRpcErrors(): array
    {
        $errors = [];
        foreach ($this->validator->failed() as $attributeName => $rule) {
            foreach ($rule as $ruleName => $parameters) {
                $errors[] = new JsonRpcInvalidParameterError($ruleName, $attributeName, $this->makeReplacements(
                    $this->getMessage($attributeName, $ruleName), $attributeName, $ruleName, $parameters
                ));
            }
        }
        
        return $errors;
    }
}
