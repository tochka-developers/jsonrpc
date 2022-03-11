<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Contracts\CustomCasterInterface;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;

class JsonRpcRequestCast
{
    /** @var array<GlobalCustomCasterInterface> */
    private array $casters = [];
    
    public function addCaster(GlobalCustomCasterInterface $caster): void
    {
        $this->casters[get_class($caster)] = $caster;
    }
    
    public function getCasterForClass(string $className): ?string
    {
        foreach ($this->casters as $casterName => $caster) {
            if ($caster->canCast($className)) {
                return $casterName;
            }
        }
        
        return null;
    }
    
    /**
     * @throws JsonRpcException
     */
    public function cast(string $casterName, Parameter $parameter, $value, string $fieldName)
    {
        if (array_key_exists($casterName, $this->casters)) {
            return $this->casters[$casterName]->cast($parameter, $value, $fieldName);
        }
        
        $caster = new $casterName();
        if (!$caster instanceof CustomCasterInterface) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }
        
        return $caster->cast($parameter, $value, $fieldName);
    }
}
