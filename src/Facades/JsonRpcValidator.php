<?php

namespace Tochka\JsonRpc\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\JsonRpc\Contracts\ValidatorInterface;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;

/**
 * @psalm-api
 *
 * @method static bool validate(array $data, array $rules, array $messages = [])
 * @method static array validateAndGetErrors(array $data, array $rules, array $messages = [])
 * @method static void validateAndThrow(array $data, array $rules, array $messages = [])
 * @method static array<InvalidParameterError> getJsonRpcErrors(array $failedRules)
 *
 * @see ValidatorInterface
 * @see \Tochka\JsonRpc\Support\Validator
 */
class JsonRpcValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ValidatorInterface::class;
    }
}
