<?php

namespace Tochka\JsonRpc\Tests\Router;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Router\RouteParser;
use Tochka\JsonRpc\Router\RouteValidation;
use Tochka\JsonRpc\Tests\Router\Examples\ParserValidationController;

class RouterParserValidationTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    protected function makeRoute(string $className, string $method): Route
    {
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $parser = new RouteParser(new Route($method, $reflectionClass->getName(), $method));
        
        return $parser->fillRoute($reflectionMethod);
    }
    
    public static function providerValidationParser(): array
    {
        return [
            'noValidation' => ['method' => 'noValidation', 'expected' => new RouteValidation()],
            'haValidationString' => [
                'method' => 'haValidationString',
                'expected' => new RouteValidation(['value' => 'required']),
            ],
            'hasValidationMultipleStrings' => [
                'method' => 'hasValidationMultipleStrings',
                'expected' => new RouteValidation(['value' => 'required|string']),
            ],
            'hasValidationArrayWithOne' => [
                'method' => 'hasValidationArrayWithOne',
                'expected' => new RouteValidation(['value' => ['required']]),
            ],
            'hasValidationArrayWithMulti' => [
                'method' => 'hasValidationArrayWithMulti',
                'expected' => new RouteValidation(['value' => ['required', 'string']]),
            ],
            'hasValidationArrayEmpty' => [
                'method' => 'hasValidationArrayEmpty',
                'expected' => new RouteValidation(),
            ],
            'hasValidationStringEmpty' => [
                'method' => 'hasValidationStringEmpty',
                'expected' => new RouteValidation(),
            ],
            'hasMultipleValidatedParams' => [
                'method' => 'hasMultipleValidatedParams',
                'expected' => new RouteValidation(['value' => 'required', 'second' => ['string', 'sometimes']])
            
            ],
            'hasObjectWithValidation' => [
                'method' => 'hasObjectWithValidation',
                'expected' => new RouteValidation(
                    [
                        'value.int' => ['required', 'integer'],
                        'value.string' => ['required', 'string'],
                        'value.bool' => ['required', 'boolean'],
                        'value.rewrite' => 'string',
                        'value.child.int' => ['required', 'integer'],
                        'value.child.string' => ['required', 'string'],
                        'value.child.bool' => ['required', 'boolean'],
                        'value.child.rewrite' => 'string',
                        'value.enumInt' => 'required',
                    ],
                    [
                        'value.int.required' => 'must be an int',
                        'value.int.integer' => 'must be an int',
                        'value.string.string' => 'must be an string',
                        'value.string.required' => 'must be an string',
                        'value.child.int.required' => 'must be an int',
                        'value.child.int.integer' => 'must be an int',
                        'value.child.string.string' => 'must be an string',
                        'value.child.string.required' => 'must be an string',
                    ],
                    [
                        'value.int' => 'number',
                        'value.string' => 'real string',
                        'value.child.int' => 'child number',
                        'value.child.string' => 'child string',
                    ]
                ),
            ],
            'hasObjectWithValidationAsRouteParam' => [
                'method' => 'hasObjectWithValidationAsRouteParam',
                'expected' => new RouteValidation(
                    [
                        'int' => ['required', 'integer'],
                        'string' => ['required', 'string'],
                        'bool' => ['required', 'boolean'],
                        'rewrite' => 'string',
                        'child.int' => ['required', 'integer'],
                        'child.string' => ['required', 'string'],
                        'child.bool' => ['required', 'boolean'],
                        'child.rewrite' => 'string',
                        'enumInt' => 'required',
                    ],
                    [
                        'int.required' => 'must be an int',
                        'int.integer' => 'must be an int',
                        'string.string' => 'must be an string',
                        'string.required' => 'must be an string',
                        'child.int.required' => 'must be an int',
                        'child.int.integer' => 'must be an int',
                        'child.string.string' => 'must be an string',
                        'child.string.required' => 'must be an string',
                    ],
                    [
                        'int' => 'number',
                        'string' => 'real string',
                        'child.int' => 'child number',
                        'child.string' => 'child string',
                    ]
                )
            ]
        ];
    }
    
    /**
     * @throws \ReflectionException
     */
    #[DataProvider('providerValidationParser')]
    public function testValidationParser(string $method, RouteValidation $expected): void
    {
        /** @var RouteParam $param */
        $route = $this->makeRoute(ParserValidationController::class, $method);
        $validation = $route->getValidation();
        $this->assertSame($expected->rules, $validation->rules);
        $this->assertSame($expected->messages, $validation->messages);
        $this->assertSame($expected->attributes, $validation->attributes);
    }
}
