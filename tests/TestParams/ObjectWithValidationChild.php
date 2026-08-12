<?php

namespace Tochka\JsonRpc\Tests\TestParams;

use Tochka\JsonRpc\Attributes\ApiValidation;
use Tochka\JsonRpc\Traits\WithValidation;

class ObjectWithValidationChild
{
    use WithValidation;
    
    public int $int;
    public string $string;
    public bool $bool;
    #[ApiValidation('string')]
    public string $rewrite;
    
    public static function rules(): array
    {
        return [
            'int' => ['required', 'integer'],
            'string' => ['required', 'string'],
            'bool' => ['required', 'boolean'],
            'rewrite' => ['int'], // should rewrite by attr
        ];
    }
    
    public static function messages(): array
    {
        return [
            'int.required' => 'must be an int',
            'int.integer' => 'must be an int',
            'string.string' => 'must be an string',
            'string.required' => 'must be an string',
        ];
    }
    
    public static function attributes(): array
    {
        return [
            'int' => 'child number',
            'string' => 'child string',
        ];
    }
}
