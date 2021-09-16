<?php

namespace Tochka\JsonRpc\Route\Parameters;

class Parameter
{
    public string $name;
    public ParameterTypeEnum $type;
    public ?Parameter $parametersInArray = null;
    public bool $nullable = false;
    public bool $required = false;
    /** @var mixed */
    public $defaultValue;
    public ?string $className = null;
    public bool $castFromDI = false;
    public bool $castFullRequest = false;
    public array $annotations = [];
    public ?string $description = null;
    
    public function __construct(string $name, ParameterTypeEnum $type)
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    public static function __set_state(array $array): self
    {
        $instance = new self($array['name'], $array['type']);
        $instance->parametersInArray = $array['parametersInArray'];
        $instance->nullable = $array['nullable'];
        $instance->required = $array['required'];
        $instance->defaultValue = $array['defaultValue'];
        $instance->className = $array['className'];
        $instance->annotations = $array['annotations'];
        $instance->description = $array['description'];
        $instance->castFromDI = $array['castFromDI'];
        $instance->castFullRequest = $array['castFullRequest'];
        
        return $instance;
    }
}
