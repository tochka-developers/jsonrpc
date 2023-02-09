<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Container\Container;
use Tochka\JsonRpc\Contracts\CasterRegistryInterface;
use Tochka\JsonRpc\Contracts\CustomCasterInterface;
use Tochka\JsonRpc\Contracts\GlobalCustomCasterInterface;
use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Standard\Exceptions\InternalErrorException;

class CasterRegistry implements CasterRegistryInterface
{
    /** @var array<class-string, GlobalCustomCasterInterface> */
    private array $casters = [];
    private Container $container;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addCaster(GlobalCustomCasterInterface $caster): void
    {
        $this->casters[$caster::class] = $caster;
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

    public function cast(string $casterName, Parameter $parameter, mixed $value, string $fieldName): ?object
    {
        if (array_key_exists($casterName, $this->casters)) {
            return $this->casters[$casterName]->cast($parameter, $value, $fieldName);
        }

        try {
            $caster = $this->container->make($casterName);
        } catch (BindingResolutionException $e) {
            throw InternalErrorException::from($e);
        }

        if (!$caster instanceof CustomCasterInterface) {
            throw new InternalErrorException(
                sprintf('Caster [%s] must implement [%s]', $casterName, CustomCasterInterface::class)
            );
        }

        return $caster->cast($parameter, $value, $fieldName);
    }
}
