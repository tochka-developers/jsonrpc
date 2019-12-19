<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class ResponseCollection implements Jsonable, Arrayable
{
    public $items = [];

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
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        if (count($this->items) === 1) {
            $response = array_shift($this->items);

            return $response->toArray();
        }

        return array_map(
            static function (JsonRpcResponse $item) {
                return $item->toArray();
            },
            $this->items
        );
    }
}
