<?php

namespace Tochka\JsonRpc\Tests\Casters;

use Tochka\JsonRpc\Exceptions\JsonRpcInvalidParameterException;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Support\JsonRpcRequest;
use Tochka\JsonRpc\Support\VoidValue;

class CasterTestHelper
{
    public static function canCastCases(PropType $expect): array
    {
        $cases = [];
        foreach (PropType::cases() as $value) {
            $cases[$value->name] = [
                'param' => new RouteParam('', $value, [], true),
                'expected' => $value === $expect
            ];
        }
        
        return $cases;
    }
    
    public static function nullCases(PropType $type): array
    {
        return [
            'null, allow null' => [
                'param' => new RouteParam('name', $type, [], true),
                'request' => JsonRpcRequest::fake(['name' => null]),
                'expected' => null,
            ],
            'null, not allow null' => [
                'param' => new RouteParam('name', $type, [], false),
                'request' => JsonRpcRequest::fake(['name' => null]),
                'expected' => JsonRpcInvalidParameterException::class
            ],
        ];
    }
    
    public static function voidCases(PropType $type): array
    {
        return [
            'void, allow void' => [
                'param' => new RouteParam('name', $type, [], true, false, true),
                'request' => JsonRpcRequest::fake([]),
                'expected' => VoidValue::class,
            ],
            'void, not allow void' => [
                'param' => new RouteParam('name', $type, [], true, false, false),
                'request' => JsonRpcRequest::fake([]),
                'expected' => JsonRpcInvalidParameterException::class
            ],
        ];
    }
}
