<?php

namespace Tochka\JsonRpc\Tests\TestParams;

use Tochka\JsonRpc\Traits\WithValidation;

class ObjectWithValidation
{
    use WithValidation;
    
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
