<?php

namespace Tochka\JsonRpc\Casters;

use Carbon\Carbon;
use Tochka\JsonRpc\Annotations\TimeZone;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;

class CarbonCaster implements GlobalCustomCasterInterface
{
    public function canCast(string $expectedType): bool
    {
        return class_exists('\\Carbon\\Carbon') && is_a($expectedType, Carbon::class, true);
    }

    public function cast(Parameter $parameter, mixed $value, string $fieldName): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) && !$value instanceof \DateTimeInterface) {
            throw InvalidParameterException::from(
                parameterName: $fieldName,
                code:          InvalidParameterError::CODE_INCORRECT_VALUE,
            );
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
            throw InvalidParameterException::from(
                parameterName: $fieldName,
                code:          InvalidParameterError::CODE_INCORRECT_VALUE,
                previous:      $e
            );
        }
    }
}
