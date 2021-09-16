<?php

namespace Tochka\JsonRpc\Support;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Pipeline\Pipeline;

class MiddlewarePipeline extends Pipeline
{
    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     * @codeCoverageIgnore
     */
    protected function carry(): \Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    // If the pipe is an instance of a Closure, we will just call it directly but
                    // otherwise we'll resolve the pipes out of the container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    return $pipe($passable, $stack);
                }

                if (!is_object($pipe)) {
                    if (is_string($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);
                    } else {
                        [$name, $parameters] = $this->parseAssociatedParams($pipe);
                    }

                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->getContainer()->make($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // If the pipe is already an object we'll just make a callable and pass it to
                    // the pipe as-is. There is no need to do any extra parsing and formatting
                    // since the object we're given was already a fully instantiated object.
                    $parameters = [$passable, $stack];
                }

                return method_exists($pipe, $this->method)
                    ? $pipe->{$this->method}(...$parameters)
                    : $pipe(...$parameters);
            };
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param array $pipe
     *
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    protected function parseAssociatedParams(array $pipe): array
    {
        [$name, $parameters] = $pipe;

        // подготавливаем аргументы для вызова метода
        $reflectionMethod = new \ReflectionMethod($name, $this->method);
        $values = [];

        $reflectionParameters = $reflectionMethod->getParameters();
        for ($i = 2, $count = count($reflectionParameters); $i < $count; $i++) {
            $reflectionParamName = $reflectionParameters[$i]->getName();

            if (isset($parameters[$reflectionParamName])) {
                $values[] = $parameters[$reflectionParamName];
                continue;
            }

            $type = $reflectionParameters[$i]->getType();
            if ($type === null || $type->isBuiltin()) {
                if (!$reflectionParameters[$i]->isOptional()) {
                    throw new \RuntimeException('Error while handling middleware: unknown parameter ' . $reflectionParamName);
                }

                // получим значение аргумента по умолчанию
                $values[] = $reflectionParameters[$i]->getDefaultValue();
                continue;
            }

            $values[] = $this->container->make($type->getName());
        }

        return [$name, $values];
    }
}
