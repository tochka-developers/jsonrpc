<?php

namespace Tochka\JsonRpc\Tests\Support;

use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\Support\OldJsonRpcResponse;
use Tochka\JsonRpc\Tests\TestHelpers\ReflectionTrait;

class ResponseCollectionTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers \Tochka\JsonRpc\DTO\JsonRpcResponseCollection::add
     * @throws \ReflectionException
     */
    public function testAdd(): void
    {
        $response1 = OldJsonRpcResponse::result(['method' => 'foo'], '11111');
        $response2 = OldJsonRpcResponse::result(['method' => 'bar'], '22222');

        $collection = new JsonRpcResponseCollection();
        $collection->add($response1);
        $collection->add($response2);

        $items = $this->getProperty($collection, 'items');

        $this->assertIsArray($items);
        $this->assertCount(2, $items);
    }

    /**
     * @covers \Tochka\JsonRpc\DTO\JsonRpcResponseCollection::empty
     */
    public function testEmpty(): void
    {
        $collection = new JsonRpcResponseCollection();

        $this->assertTrue($collection->empty());
        $collection->add(OldJsonRpcResponse::result(['method' => 'foo'], '11111'));

        $this->assertFalse($collection->empty());
    }

    /**
     * @covers \Tochka\JsonRpc\DTO\JsonRpcResponseCollection::toArray
     */
    public function testToArraySingle(): void
    {
        $response = OldJsonRpcResponse::result(['method' => 'foo'], '11111');

        $collection = new JsonRpcResponseCollection();
        $collection->add($response);

        $result = $collection->toArray();

        $this->assertEquals($response->toArray(), $result);
    }

    /**
     * @covers \Tochka\JsonRpc\DTO\JsonRpcResponseCollection::toArray
     */
    public function testToArrayMultiple(): void
    {
        $response1 = OldJsonRpcResponse::result(['method' => 'foo'], '11111');
        $response2 = OldJsonRpcResponse::result(['method' => 'bar'], '22222');

        $collection = new JsonRpcResponseCollection();
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
     * @covers \Tochka\JsonRpc\DTO\JsonRpcResponseCollection::toJson
     */
    public function testToJson(): void
    {
        $response1 = OldJsonRpcResponse::result(['method' => 'foo'], '11111');
        $response2 = OldJsonRpcResponse::result(['method' => 'bar'], '22222');

        $collection = new JsonRpcResponseCollection();
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
