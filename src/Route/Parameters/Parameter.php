<?php

namespace Tochka\JsonRpc\Route\Parameters;

use Tochka\JsonRpc\Contracts\ApiAnnotationInterface;

class Parameter
{
    public string $name;
    public ParameterTypeEnum $type;
    public ?Parameter $parametersInArray = null;
    public bool $nullable = false;
    public bool $required = false;
    public mixed $defaultValue;
    public bool $hasDefaultValue = false;
    /** @var class-string|null */
    public ?string $className = null;
    public bool $castFromDI = false;
    public bool $castFullRequest = false;
    /** @var array<ApiAnnotationInterface> */
    public array $annotations = [];
    public ?string $description = null;

    public function __construct(string $name, ParameterTypeEnum $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @param array{
     *     name: string,
     *     type: ParameterTypeEnum,
     *     parametersInArray: Parameter|null,
     *     nullable: bool,
     *     required: bool,
     *     defaultValue: mixed,
     *     hasDefaultValue?: bool,
     *     className: class-string,
     *     annotations: array<ApiAnnotationInterface>,
     *     description: string|null,
     *     castFromDI: bool,
     *     castFullRequest: bool
     * } $array
     * @return self
     */
    public static function __set_state(array $array): self
    {
        $instance = new self($array['name'], $array['type']);
        $instance->parametersInArray = $array['parametersInArray'];
        $instance->nullable = $array['nullable'];
        $instance->required = $array['required'];
        $instance->defaultValue = $array['defaultValue'];
        $instance->hasDefaultValue = $array['hasDefaultValue'] ?? false;
        $instance->className = $array['className'];
        $instance->annotations = $array['annotations'];
        $instance->description = $array['description'];
        $instance->castFromDI = $array['castFromDI'];
        $instance->castFullRequest = $array['castFullRequest'];

        return $instance;
    }
}
