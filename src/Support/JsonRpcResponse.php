<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class JsonRpcResponse implements Jsonable, Arrayable
{
    public string $jsonrpc = '2.0';
    public string|int|null $id = null;
    public mixed $error = null;
    public mixed $result = null;

    public static function result(mixed $result, string|int|null $id = null): self
    {
        $instance = new self();
        $instance->result = $result;
        $instance->id = $id;

        return $instance;
    }

    public static function error(mixed $error, string|int|null $id = null): self
    {
        $instance = new self();
        $instance->error = $error;
        $instance->id = $id;

        return $instance;
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
        $result = [];
        $result['jsonrpc'] = $this->jsonrpc;
        if ($this->id !== null) {
            $result['id'] = $this->id;
        }

        if ($this->error !== null) {
            $result['error'] = $this->valueToArray($this->error);
        } else {
            $result['result'] = $this->valueToArray($this->result);
        }

        return $result;
    }

    private function valueToArray($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }

        return $value;
    }
}
