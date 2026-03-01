<?php

namespace OptivacConsent\Core;

use ReflectionClass;
use ReflectionNamedType;
use Exception;

class Container
{
    private array $instances = [];
    private array $bindings  = [];

    public function bind(string $abstract, string|\Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function get(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];

            if ($concrete instanceof \Closure) {
                return $this->instances[$abstract] = $concrete($this);
            }

            $abstract = $concrete;
        }

        return $this->instances[$abstract] = $this->resolve($abstract);
    }

    private function resolve(string $class)
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Class {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if (!$type instanceof ReflectionNamedType) {
                throw new Exception("Unsupported union type in {$class}");
            }

            if ($type->isBuiltin()) {
                throw new Exception(
                    "Cannot resolve builtin type {$param->getName()} in {$class}"
                );
            }

            $dependencies[] = $this->get($type->getName());
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
