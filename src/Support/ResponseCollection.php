<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class ResponseCollection implements Jsonable, Arrayable
{
    /** @var array<JsonRpcResponse> */
    public array $items = [];

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
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        if (count($this->items) === 1) {
            $response = array_shift($this->items);

            return $response->toArray();
        }

        return array_map(
            static fn(JsonRpcResponse $item) => $item->toArray(),
            $this->items
        );
    }
}
