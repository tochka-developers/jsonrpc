<?php

namespace Tochka\JsonRpc\Tests\Units;

use Illuminate\Contracts\Debug\ExceptionHandler as LaravelExceptionHandlerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
use Tochka\JsonRpc\ExceptionHandler;
use Tochka\JsonRpc\Exceptions\Errors\HttpExceptionError;
use Tochka\JsonRpc\Standard\DTO\JsonRpcError;
use Tochka\JsonRpc\Standard\Exceptions\Additional\AdditionalJsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\Additional\InvalidParameterException;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InternalError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParameterError;
use Tochka\JsonRpc\Standard\Exceptions\Errors\InvalidParametersError;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;

/**
 * @covers \Tochka\JsonRpc\ExceptionHandler
 */
class ExceptionHandlerTest extends DefaultTestCase
{
    public function testHandleHttpException(): void
    {
        $exception = new SymfonyHttpException(404, 'Not Found');

        $globalHandler = \Mockery::mock(LaravelExceptionHandlerInterface::class);
        $globalHandler->shouldReceive('report')
            ->with($exception)
            ->once();

        $handler = new ExceptionHandler($globalHandler);

        $actual = $handler->handle($exception);

        $expected = new JsonRpcError(
            JsonRpcException::CODE_INTERNAL_ERROR,
            JsonRpcException::MESSAGE_INTERNAL_ERROR,
            new HttpExceptionError($exception)
        );

        self::assertEquals($expected, $actual);
    }

    public function testHandleJsonRpcException(): void
    {
        $exception = InvalidParameterException::from('foo', 'required');

        $globalHandler = \Mockery::mock(LaravelExceptionHandlerInterface::class);
        $globalHandler->shouldReceive('report')
            ->with($exception)
            ->once();

        $handler = new ExceptionHandler($globalHandler);

        $actual = $handler->handle($exception);

        $expected = new JsonRpcError(
            AdditionalJsonRpcException::CODE_INVALID_PARAMETERS,
            AdditionalJsonRpcException::MESSAGE_INVALID_PARAMETERS,
            new InvalidParametersError([new InvalidParameterError('foo', 'required')])
        );

        self::assertEquals($expected, $actual);
    }

    public function testHandleAnotherException(): void
    {
        $exception = new \RuntimeException('Some message', 300);

        $globalHandler = \Mockery::mock(LaravelExceptionHandlerInterface::class);
        $globalHandler->shouldReceive('report')
            ->with($exception)
            ->once();

        $handler = new ExceptionHandler($globalHandler);

        $actual = $handler->handle($exception);

        $expected = new JsonRpcError(
            JsonRpcException::CODE_INTERNAL_ERROR,
            JsonRpcException::MESSAGE_INTERNAL_ERROR,
            new InternalError($exception)
        );

        self::assertEquals($expected, $actual);
    }
}
