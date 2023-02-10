<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions\Errors;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tochka\JsonRpc\Exceptions\Errors\HttpExceptionError;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\Errors\HttpExceptionError
 */
class HttpExceptionErrorTest extends DefaultTestCase
{
    public function testToArrayHasMessage(): void
    {
        $expectedCode = 404;
        $expectedMessage = 'Not Found';
        $expectedException = new HttpException($expectedCode, $expectedMessage);

        $error = new HttpExceptionError($expectedException);

        $expected = [
            'exception' => [
                'name' => HttpException::class,
                'code' => $expectedCode,
                'message' => $expectedMessage
            ]
        ];

        self::assertEquals($expected, $error->toArray());
    }

    public function testToArrayNoMessage(): void
    {
        $expectedCode = 302;
        $expectedMessage = Response::$statusTexts[$expectedCode];
        $expectedException = new HttpException($expectedCode);

        $error = new HttpExceptionError($expectedException);

        $expected = [
            'exception' => [
                'name' => HttpException::class,
                'code' => $expectedCode,
                'message' => $expectedMessage
            ]
        ];

        self::assertEquals($expected, $error->toArray());
    }
}
