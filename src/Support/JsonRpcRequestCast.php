<?php

namespace Tochka\JsonRpc\Support;

use Tochka\JsonRpc\Contracts\CustomCasterInterface;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Exceptions\JsonRpcException;

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
    public function cast(string $casterName, string $className, $value, string $fieldName)
    {
        if (array_key_exists($casterName, $this->casters)) {
            return $this->casters[$casterName]->cast($className, $value, $fieldName);
        }
        
        $caster = new $casterName();
        if (!$caster instanceof CustomCasterInterface) {
            throw new JsonRpcException(JsonRpcException::CODE_INTERNAL_ERROR);
        }
        
        return $caster->cast($className, $value, $fieldName);
    }
}
