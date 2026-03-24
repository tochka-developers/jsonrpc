<?php

namespace Tochka\JsonRpc\Tests\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Support\JsonRpcResponse;

#[CoversClass(JsonRpcResponse::class)]
class JsonRpcResponseTest extends TestCase
{
    public function testResult(): void
    {
        $data = ['data' => 'data'];
        $id = '12345';

        $response = JsonRpcResponse::result($data, $id);

        $this->assertEquals($data, $response->result);
        $this->assertEquals('2.0', $response->jsonrpc);
        $this->assertEquals($id, $response->id);
        $this->assertNull($response->error);
    }

    public function testError(): void
    {
        $error = ['error' => 'error'];
        $id = '12345';

        $response = JsonRpcResponse::error($error, $id);

        $this->assertEquals($error, $response->error);
        $this->assertEquals('2.0', $response->jsonrpc);
        $this->assertEquals($id, $response->id);
        $this->assertNull($response->result);
    }

    public function testToArrayResult(): void
    {
        $data = ['data' => 'data'];
        $id = '12345';
        $result = [
            'jsonrpc' => '2.0',
            'result'  => $data,
            'id'      => $id,
        ];

        $response = JsonRpcResponse::result($data, $id)->toArray();

        $this->assertEquals($result, $response);
    }

    public function testToArrayError(): void
    {
        $error = ['error' => 'message'];
        $id = '12345';
        $result = [
            'jsonrpc' => '2.0',
            'error'   => $error,
            'id'      => $id,
        ];

        $response = JsonRpcResponse::error($error, $id)->toArray();

        $this->assertEquals($result, $response);
    }

    public function testToJson(): void
    {
        $data = ['data' => 'data'];
        $id = '12345';
        $result = json_encode([
            'jsonrpc' => '2.0',
            'result'  => $data,
            'id'      => $id,
        ], JSON_UNESCAPED_UNICODE);

        $response = JsonRpcResponse::result($data, $id)->toJson();

        $this->assertJsonStringEqualsJsonString($result, $response);
    }
}
