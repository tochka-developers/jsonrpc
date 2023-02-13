<?php

namespace Tochka\JsonRpc\Tests\Units\Support;

use Psr\Http\Message\ServerRequestInterface;
use Tochka\JsonRpc\DTO\JsonRpcServerRequest;
use Tochka\JsonRpc\Standard\DTO\JsonRpcRequest;
use Tochka\JsonRpc\Standard\Exceptions\InvalidRequestException;
use Tochka\JsonRpc\Standard\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Standard\Exceptions\ParseErrorException;
use Tochka\JsonRpc\Support\Parser;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\Support\Parser
 */
class ParserTest extends DefaultTestCase
{
    public function testParseEmptyContent(): void
    {
        $parser = new Parser();
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getBody')
            ->once()
            ->with()
            ->andReturn('');

        self::expectException(InvalidRequestException::class);
        self::expectExceptionMessage(JsonRpcException::MESSAGE_INVALID_REQUEST);

        $parser->parse($request);
    }

    public function testParseJsonError(): void
    {
        $parser = new Parser();
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getBody')
            ->once()
            ->with()
            ->andReturn('{"json",}');

        self::expectException(ParseErrorException::class);
        self::expectExceptionMessage(JsonRpcException::MESSAGE_PARSE_ERROR);

        $parser->parse($request);
    }

    public function testParseOneRequest(): void
    {
        $parser = new Parser();
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getBody')
            ->once()
            ->with()
            ->andReturn('{"jsonrpc": "2.0", "method": "test", "params": []}');

        $actual = $parser->parse($request);

        $expected = [
            new JsonRpcServerRequest($request, new JsonRpcRequest('test', []))
        ];

        self::assertEquals($expected, $actual);
    }

    public function testParseMultipleRequest(): void
    {
        $parser = new Parser();
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getBody')
            ->once()
            ->with()
            ->andReturn(
                '[{"jsonrpc": "2.0", "method": "fooMethod", "params": []},{"jsonrpc": "2.0", "method": "barMethod", "params": []}]'
            );

        $actual = $parser->parse($request);

        $expected = [
            new JsonRpcServerRequest($request, new JsonRpcRequest('fooMethod', [])),
            new JsonRpcServerRequest($request, new JsonRpcRequest('barMethod', [])),
        ];

        self::assertEquals($expected, $actual);
    }
}
