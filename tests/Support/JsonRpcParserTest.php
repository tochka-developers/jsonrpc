<?php

namespace Tochka\JsonRpc\Tests\Support;

use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Support\JsonRpcParser;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class JsonRpcParserTest extends TestCase
{
    public $parser;

    public function setUp(): void
    {
        parent::setUp();
        $this->parser = new JsonRpcParser();
    }

    /**
     * @covers \Tochka\JsonRpc\Support\JsonRpcParser::parse
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function testParseEmptyContent(): void
    {
        $this->expectException(JsonRpcException::class);
        $this->expectExceptionCode(JsonRpcException::CODE_INVALID_REQUEST);

        $this->parser->parse('');
    }

    /**
     * @covers \Tochka\JsonRpc\Support\JsonRpcParser::parse
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     * @throws \JsonException
     */
    public function testParseInvalidJson(): void
    {
        $this->expectException(JsonRpcException::class);
        $this->expectExceptionCode(JsonRpcException::CODE_PARSE_ERROR);

        $this->parser->parse('{"data":"invalid}');
    }

    /**
     * @covers \Tochka\JsonRpc\Support\JsonRpcParser::parse
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function testParseOneRequest(): void
    {
        $call = (object) [
            'method' => 'foo',
        ];
        $requests = $this->parser->parse(json_encode($call));

        $this->assertIsArray($requests);

        $request = array_shift($requests);
        $this->assertInstanceOf(JsonRpcRequest::class, $request);
        $this->assertEquals($call, $request->call);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\JsonRpcParser::parse
     * @throws \Tochka\JsonRpc\Exceptions\JsonRpcException
     */
    public function testParseBatchRequest(): void
    {
        $call = [
            (object) ['method' => 'foo'],
            (object) ['method' => 'bar'],
        ];

        $requests = $this->parser->parse(json_encode($call));

        $this->assertIsArray($requests);

        $request = array_shift($requests);
        $this->assertInstanceOf(JsonRpcRequest::class, $request);
        $this->assertEquals($call[0], $request->call);

        $request = array_shift($requests);
        $this->assertInstanceOf(JsonRpcRequest::class, $request);
        $this->assertEquals($call[1], $request->call);
    }
}
