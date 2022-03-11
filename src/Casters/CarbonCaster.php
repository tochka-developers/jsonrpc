<?php

namespace Tochka\JsonRpc\Casters;

use Carbon\Carbon;
use Tochka\JsonRpc\Annotations\TimeZone;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Route\Parameters\Parameter;

class CarbonCaster implements GlobalCustomCasterInterface
{
    public function canCast(string $expectedType): bool
    {
        return class_exists('\\Carbon\\Carbon') && is_a($expectedType, '\\Carbon\\Carbon', true);
    }
    
    /**
     * @throws JsonRpcInvalidParameterException
     */
    public function cast(Parameter $parameter, $value, string $fieldName): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        
        try {
            $tz = null;
            foreach ($parameter->annotations as $annotation) {
                if ($annotation instanceof TimeZone) {
                    $tz = $annotation->timezone;
                    break;
                }
            }
    
            return Carbon::parse($value, $tz);
        } catch (\Throwable $e) {
            throw new JsonRpcInvalidParameterException('error', $fieldName, $e->getMessage(), $e);
        }
    }
}
