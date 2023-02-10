<?php

namespace Tochka\JsonRpc\DTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Tochka\JsonRpc\Standard\DTO\JsonRpcResponse;

/**
 * @psalm-api
 * @psalm-import-type JsonRpcResponseArray from JsonRpcResponse
 * @psalm-suppress MissingTemplateParam
 */
class JsonRpcResponseCollection implements Jsonable, Arrayable, \JsonSerializable
{
    /** @var array<JsonRpcResponse> */
    private array $items = [];

    public function add(JsonRpcResponse $response): void
    {
        $this->items[] = $response;
    }

    public function empty(): bool
    {
        return empty($this->items);
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<int, JsonRpcResponseArray>|JsonRpcResponseArray
     */
    public function toArray(): array
    {
        if (count($this->items) === 1) {
            $response = array_shift($this->items);

            return $response->toArray();
        }

        return array_map(
            static fn (JsonRpcResponse $item) => $item->toArray(),
            $this->items
        );
    }

    public function jsonSerialize(): array
    {
        if (count($this->items) === 1) {
            $response = array_shift($this->items);

            return $response->jsonSerialize();
        }

        return array_map(
            static fn (JsonRpcResponse $item) => $item->jsonSerialize(),
            $this->items
        );
    }
}
