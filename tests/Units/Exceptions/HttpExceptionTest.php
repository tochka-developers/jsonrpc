<?php

namespace Tochka\JsonRpc\Tests\Units\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
use Tochka\JsonRpc\Exceptions\Errors\HttpExceptionError;
use Tochka\JsonRpc\Exceptions\HttpException;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Exceptions\HttpException
 */
class HttpExceptionTest extends DefaultTestCase
{
    public function test__construct(): void
    {
        $httpException = new SymfonyHttpException(404, 'Not Found');
        $exception = new HttpException($httpException);

        $expected = new JsonRpcError(
            JsonRpcException::CODE_INTERNAL_ERROR,
            JsonRpcException::MESSAGE_INTERNAL_ERROR,
            new HttpExceptionError($httpException)
        );

        self::assertEquals($expected, $exception->getJsonRpcError());
    }
}
