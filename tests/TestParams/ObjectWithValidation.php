<?php

namespace Tochka\JsonRpc\Tests\TestParams;

use Tochka\JsonRpc\Contracts\ShouldValidated;

class ObjectWithValidation implements ShouldValidated
{
    public int $int;
    public string $string;
    public bool $bool;
    
    public static function rules(): array
    {
        return [
            'int' => ['required', 'integer'],
            'string' => ['required', 'string'],
            'bool' => ['required', 'boolean'],
        ];
    }
}
