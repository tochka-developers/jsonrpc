<?php

namespace Tochka\JsonRpc\Exceptions;

class JsonRpcInvalidParametersException extends JsonRpcException
{
    /** @var array<JsonRpcInvalidParameterError> */
    private array $errors;
    
    public function __construct(array $errors, ?\Throwable $previous = null)
    {
        $this->errors = $errors;
        
        parent::__construct(
            JsonRpcException::CODE_INVALID_PARAMETERS,
            null,
            array_map(static fn(JsonRpcInvalidParameterError $item) => $item->toArray(), $this->errors),
            $previous
        );
    }
    
    /**
     * @return array<JsonRpcInvalidParameterError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
