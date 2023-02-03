<?php

namespace Tochka\JsonRpc\DTO;

use Tochka\JsonRpc\Contracts\ApiAnnotationInterface;
use Tochka\JsonRpc\Route\Parameters\Parameter;

final class JsonRpcRoute
{
    public string $serverName;
    public ?string $group;
    public ?string $action;
    public string $jsonRpcMethodName;
    /** @var class-string|null */
    public ?string $controllerClass = null;
    public ?string $controllerMethod = null;
    /** @var array<string, Parameter> */
    public array $parameters = [];
    public ?Parameter $result = null;
    /** @var array<ApiAnnotationInterface> */
    public array $annotations = [];

    public function __construct(
        string $serverName,
        string $jsonRpcMethodName,
        string $group = null,
        string $action = null
    ) {
        $this->serverName = $serverName;
        $this->jsonRpcMethodName = $jsonRpcMethodName;
        $this->group = $group;
        $this->action = $action;
    }

    public function getRouteName(): string
    {
        return implode(
            '@',
            [
                $this->serverName,
                $this->group,
                $this->action,
                $this->jsonRpcMethodName
            ]
        );
    }

    /**
     * @param array{
     *     serverName: string,
     *     jsonRpcMethodName: string,
     *     group?: string,
     *     action?: string,
     *     controllerClass?: class-string,
     *     controllerMethod?: string,
     *     parameters?: array<string, Parameter>,
     *     result?: Parameter,
     *     annotations?: array<ApiAnnotationInterface>
     * } $array
     */
    public static function __set_state(array $array): self
    {
        $instance = new self(
            $array['serverName'],
            $array['jsonRpcMethodName'],
            $array['group'] ?? null,
            $array['action'] ?? null
        );
        $instance->controllerClass = $array['controllerClass'] ?? null;
        $instance->controllerMethod = $array['controllerMethod'] ?? null;
        $instance->parameters = $array['parameters'] ?? [];
        $instance->result = $array['result'] ?? null;
        $instance->annotations = $array['annotations'] ?? [];

        return $instance;
    }
}
