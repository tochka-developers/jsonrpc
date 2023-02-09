<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;

/**
 * @psalm-api
 */
interface ValidatorInterface
{
    public function validate(array $data, array $rules, array $messages = []): bool;

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return array<InvalidParameterError>
     */
    public function validateAndGetErrors(array $data, array $rules, array $messages = []): array;

    public function validateAndThrow(array $data, array $rules, array $messages = []): void;
}
