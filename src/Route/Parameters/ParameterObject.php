<?php

namespace Tochka\JsonRpc\Route\Parameters;

use Tochka\JsonRpc\Contracts\ApiAnnotationInterface;

class ParameterObject
{
    /** @var class-string */
    public string $className;
    /** @var array<string, Parameter> */
    public ?array $properties = null;
    /** @var class-string|null */
    public ?string $customCastByCaster = null;
    /** @var array<ApiAnnotationInterface> */
    public array $annotations = [];

    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * @param array{
     *     className: class-string,
     *     properties: array<string, Parameter>|null,
     *     customCastByCaster: class-string|null,
     *     annotations: array<ApiAnnotationInterface>
     * } $array
     * @return self
     */
    public static function __set_state(array $array): self
    {
        $instance = new self($array['className']);
        $instance->properties = $array['properties'];
        $instance->customCastByCaster = $array['customCastByCaster'];
        $instance->annotations = $array['annotations'];

        return $instance;
    }
}
