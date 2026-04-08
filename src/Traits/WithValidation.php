<?php

namespace Tochka\JsonRpc\Traits;

trait WithValidation
{
    /**
     * @codeCoverageIgnore
     * @return array<string, array|string>
     */
    public static function rules(): array
    {
        return [];
    }
    
    /**
     * @codeCoverageIgnore
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [];
    }
    
    /**
     * @codeCoverageIgnore
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [];
    }
}
