<?php

namespace Tochka\JsonRpc\Tests\Router\Examples;

use Tochka\JsonRpc\Attributes\ApiParams;
use Tochka\JsonRpc\Attributes\ApiValidation;
use Tochka\JsonRpc\Tests\TestParams\ObjectWithValidation;

class ParserValidationController
{
    public function noValidation($value): void
    {
    }
    
    public function haValidationString(#[ApiValidation('required')] $value): void
    {
    }
    
    public function hasValidationMultipleStrings(#[ApiValidation('required|string')] $value): void
    {
    }
    
    public function hasValidationArrayWithOne(#[ApiValidation(['required'])] $value): void
    {
    }
    
    public function hasValidationArrayWithMulti(#[ApiValidation(['required', 'string'])] $value): void
    {
    }
    
    public function hasValidationArrayEmpty(#[ApiValidation([])] $value): void
    {
    }
    
    public function hasValidationStringEmpty(#[ApiValidation('')] $value): void
    {
    }
    
    public function hasMultipleValidatedParams(
        #[ApiValidation('required')] $value,
        #[ApiValidation(['string', 'sometimes'])] $second
    ): void {
    }
    
    public function hasObjectWithValidation(ObjectWithValidation $value): void
    {
    }
    
    public function hasObjectWithValidationAsRouteParam(#[ApiParams] ObjectWithValidation $value): void
    {
    }
}
