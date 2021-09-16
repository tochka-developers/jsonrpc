<?php

namespace Tochka\JsonRpc\Route\Parameters;

class ParameterObject
{
    public string $className;
    /** @var array<string, Parameter> */
    public ?array $properties = null;
    public ?string $customCastByCaster = null;
    public array $annotations = [];
    
    public function __construct(string $className)
    {
        $this->className = $className;
    }
    
    /**
     * @param array $array
     * @return static
     */
    public static function __set_state(array $array): self
    {
        $instance = new self($array['className']);
        $instance->properties = $array['properties'];
        $instance->customCastByCaster = $array['customCastByCaster'];
        
        return $instance;
    }
}
