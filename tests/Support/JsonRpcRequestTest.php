<?php

namespace Tochka\JsonRpc\Tests\Support;

use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class JsonRpcRequestTest extends TestCase
{
    /**
     * @covers \Tochka\JsonRpc\Support\JsonRpcRequest::__construct
     */
    public function testConstruct(): void
    {
        $call = (object) [
            'method' => 'foo',
            'id'     => '12345',
        ];
        $request = new JsonRpcRequest($call);

        $this->assertEquals($call, $request->call);
        $this->assertEquals($call->id, $request->id);
    }
}
