<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Analytics\Dependencies\DI\Definition\Source;

use Automattic\WooCommerce\Analytics\Dependencies\DI\Definition\ObjectDefinition;
use Automattic\WooCommerce\Analytics\Dependencies\DI\Definition\ObjectDefinition\MethodInjection;
use Automattic\WooCommerce\Analytics\Dependencies\DI\Definition\Reference;
use ReflectionNamedType;

/**
 * Reads Automattic\WooCommerce\Analytics\Dependencies\DI class definitions using reflection.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ReflectionBasedAutowiring implements DefinitionSource, Autowiring
{
    public function autowire(string $name, ?ObjectDefinition $definition = null)
    {
        $className = $definition ? $definition->getClassName() : $name;

        if (!class_exists($className) && !interface_exists($className)) {
            return $definition;
        }

        $definition = $definition ?: new ObjectDefinition($name);

        // Constructor
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        if ($constructor && $constructor->isPublic()) {
            $constructorInjection = MethodInjection::constructor($this->getParametersDefinition($constructor));
            $definition->completeConstructorInjection($constructorInjection);
        }

        return $definition;
    }

    public function getDefinition(string $name)
    {
        return $this->autowire($name);
    }

    /**
     * Autowiring cannot guess all existing definitions.
     */
    public function getDefinitions() : array
    {
        return [];
    }

    /**
     * Read the type-hinting from the parameters of the function.
     */
    private function getParametersDefinition(\ReflectionFunctionAbstract $constructor) : array
    {
        $parameters = [];

        foreach ($constructor->getParameters() as $index => $parameter) {
            // Skip optional parameters
            if ($parameter->isOptional()) {
                continue;
            }

            $parameterType = $parameter->getType();
            if (!$parameterType) {
                // No type
                continue;
            }
            if (!$parameterType instanceof ReflectionNamedType) {
                // Union types are not supported
                continue;
            }
            if ($parameterType->isBuiltin()) {
                // Primitive types are not supported
                continue;
            }

            $parameters[$index] = new Reference($parameterType->getName());
        }

        return $parameters;
    }
}
