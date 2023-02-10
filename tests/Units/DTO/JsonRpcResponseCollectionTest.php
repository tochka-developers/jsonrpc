<?php

namespace Tochka\JsonRpc\Tests\Units\DTO;

use Tochka\JsonRpc\DTO\JsonRpcResponseCollection;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;
use Tochka\JsonRpc\Tests\Units\DefaultTestCase;

/**
 * @covers \Tochka\JsonRpc\DTO\JsonRpcResponseCollection
 */
class JsonRpcResponseCollectionTest extends DefaultTestCase
{
    public function testEmpty(): void
    {
        $emptyCollection = new JsonRpcResponseCollection();
        $notEmptyCollection = new JsonRpcResponseCollection();
        $notEmptyCollection->add(new JsonRpcResponse());

        self::assertTrue($emptyCollection->empty());
        self::assertFalse($notEmptyCollection->empty());
    }

    public function testToArrayMultipleResponse(): void
    {
        $expectedResponse1 = new JsonRpcResponse('id1', true);
        $expectedResponse2 = new JsonRpcResponse('id2', false);

        $collection = new JsonRpcResponseCollection();
        $collection->add($expectedResponse1);
        $collection->add($expectedResponse2);

        $actual = $collection->toArray();

        self::assertEquals([$expectedResponse1->toArray(), $expectedResponse2->toArray()], $actual);
    }

    public function testToArraySingleResponse(): void
    {
        $expectedResponse1 = new JsonRpcResponse('id1', true);

        $collection = new JsonRpcResponseCollection();
        $collection->add($expectedResponse1);

        $actual = $collection->toArray();

        self::assertEquals($expectedResponse1->toArray(), $actual);
    }

    public function testJsonSerializeMultipleResponse(): void
    {
        $expectedResponse1 = new JsonRpcResponse('id1', true);
        $expectedResponse2 = new JsonRpcResponse('id2', false);

        $collection = new JsonRpcResponseCollection();
        $collection->add($expectedResponse1);
        $collection->add($expectedResponse2);

        $actual = $collection->jsonSerialize();

        self::assertEquals([$expectedResponse1->jsonSerialize(), $expectedResponse2->jsonSerialize()], $actual);
    }

    public function testJsonSerializeSingleResponse(): void
    {
        $expectedResponse1 = new JsonRpcResponse('id1', true);

        $collection = new JsonRpcResponseCollection();
        $collection->add($expectedResponse1);

        $actual = $collection->jsonSerialize();

        self::assertEquals($expectedResponse1->jsonSerialize(), $actual);
    }

    public function testToJsonMultipleResponse(): void
    {
        $expectedResponse1 = new JsonRpcResponse('id1', true);
        $expectedResponse2 = new JsonRpcResponse('id2', false);

        $collection = new JsonRpcResponseCollection();
        $collection->add($expectedResponse1);
        $collection->add($expectedResponse2);

        $actual = $collection->toJson();

        $expectedJson = json_encode(
            [$expectedResponse1->toArray(), $expectedResponse2->toArray()],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        self::assertEquals($expectedJson, $actual);
    }

    public function testToJsonSingleResponse(): void
    {
        $expectedResponse1 = new JsonRpcResponse('id1', true);

        $collection = new JsonRpcResponseCollection();
        $collection->add($expectedResponse1);

        $actual = $collection->toJson();

        $expectedJson = json_encode($expectedResponse1->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        self::assertEquals($expectedJson, $actual);
    }
}
