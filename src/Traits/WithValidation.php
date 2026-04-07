<?php

namespace Tochka\JsonRpc\Traits;

trait WithValidation
{
    /**
     * @return array<string, array|string>
     */
    public static function rules(): array
    {
        return [];
    }
    
    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [];
    }
    
    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [];
    }
}
