<?php

namespace Tochka\JsonRpc\Tests\Support;

use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Support\JsonRpcResponse;
use Tochka\JsonRpc\Support\ResponseCollection;
use Tochka\JsonRpc\Tests\TestHelpers\ReflectionTrait;

class ResponseCollectionTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers \Tochka\JsonRpc\Support\ResponseCollection::add
     * @throws \ReflectionException
     */
    public function testAdd(): void
    {
        $response1 = JsonRpcResponse::result(['method' => 'foo'], '11111');
        $response2 = JsonRpcResponse::result(['method' => 'bar'], '22222');

        $collection = new ResponseCollection();
        $collection->add($response1);
        $collection->add($response2);

        $items = $this->getProperty($collection, 'items');

        $this->assertIsArray($items);
        $this->assertCount(2, $items);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\ResponseCollection::empty
     */
    public function testEmpty(): void
    {
        $collection = new ResponseCollection();

        $this->assertTrue($collection->empty());
        $collection->add(JsonRpcResponse::result(['method' => 'foo'], '11111'));

        $this->assertFalse($collection->empty());
    }

    /**
     * @covers \Tochka\JsonRpc\Support\ResponseCollection::toArray
     */
    public function testToArraySingle(): void
    {
        $response = JsonRpcResponse::result(['method' => 'foo'], '11111');

        $collection = new ResponseCollection();
        $collection->add($response);

        $result = $collection->toArray();

        $this->assertEquals($response->toArray(), $result);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\ResponseCollection::toArray
     */
    public function testToArrayMultiple(): void
    {
        $response1 = JsonRpcResponse::result(['method' => 'foo'], '11111');
        $response2 = JsonRpcResponse::result(['method' => 'bar'], '22222');

        $collection = new ResponseCollection();
        $collection->add($response1);
        $collection->add($response2);

        $result = $collection->toArray();

        $this->assertIsArray($result);

        $responseResult1 = array_shift($result);
        $this->assertEquals($response1->toArray(), $responseResult1);

        $responseResult2 = array_shift($result);
        $this->assertEquals($response2->toArray(), $responseResult2);
    }

    /**
     * @covers \Tochka\JsonRpc\Support\ResponseCollection::toJson
     */
    public function testToJson(): void
    {
        $response1 = JsonRpcResponse::result(['method' => 'foo'], '11111');
        $response2 = JsonRpcResponse::result(['method' => 'bar'], '22222');

        $collection = new ResponseCollection();
        $collection->add($response1);
        $collection->add($response2);

        $result = $collection->toJson();

        $expected = json_encode([
            $response1->toArray(),
            $response2->toArray(),
        ]);

        $this->assertJsonStringEqualsJsonString($expected, $result);
    }
}
