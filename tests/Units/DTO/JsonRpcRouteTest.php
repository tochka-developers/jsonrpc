<?php

namespace Tochka\JsonRpc\Tests\Units\DTO;

use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\DTO\JsonRpcRoute;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterTypeEnum;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\DTO\JsonRpcRoute
 */
class JsonRpcRouteTest extends DefaultTestCase
{
    public function testGetRouteName(): void
    {
        $expectedServerName = 'testServer';
        $expectedMethodName = 'testMethod';
        $expectedGroup = 'testGroup';
        $expectedAction = 'testAction';
        $expectedRouteName = $expectedServerName . '@' . $expectedGroup . '@' . $expectedAction . '@' . $expectedMethodName;

        $route = new JsonRpcRoute($expectedServerName, $expectedMethodName, $expectedGroup, $expectedAction);
        $actualRouteName = $route->getRouteName();

        self::assertEquals($expectedRouteName, $actualRouteName);
    }

    public function test__set_state(): void
    {
        $expectedServerName = 'testServer';
        $expectedMethodName = 'testMethod';
        $expectedGroup = 'testGroup';
        $expectedAction = 'testAction';
        $expectedControllerClass = 'testController';
        $expectedControllerMethod = 'testControllerMethod';
        $expectedParameters = ['fooField' => new Parameter('fooField', ParameterTypeEnum::TYPE_FLOAT())];
        $expectedResult = new Parameter('barField', ParameterTypeEnum::TYPE_ARRAY());
        $expectedAnnotations = [new ApiIgnore()];

        $array = [
            'serverName' => $expectedServerName,
            'jsonRpcMethodName' => $expectedMethodName,
            'group' => $expectedGroup,
            'action' => $expectedAction,
            'controllerClass' => $expectedControllerClass,
            'controllerMethod' => $expectedControllerMethod,
            'parameters' => $expectedParameters,
            'result' => $expectedResult,
            'annotations' => $expectedAnnotations,
        ];

        $route = JsonRpcRoute::__set_state($array);

        self::assertEquals($expectedServerName, $route->serverName);
        self::assertEquals($expectedMethodName, $route->jsonRpcMethodName);
        self::assertEquals($expectedGroup, $route->group);
        self::assertEquals($expectedAction, $route->action);
        self::assertEquals($expectedControllerClass, $route->controllerClass);
        self::assertEquals($expectedControllerMethod, $route->controllerMethod);
        self::assertEquals($expectedParameters, $route->parameters);
        self::assertEquals($expectedResult, $route->result);
        self::assertEquals($expectedAnnotations, $route->annotations);
    }
}
