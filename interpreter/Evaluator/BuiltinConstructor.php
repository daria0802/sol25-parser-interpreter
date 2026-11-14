<?php
/**
 * BuiltinConstructor – handles instantiation of built-in classes in SOL25.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Evaluator;

use IPP\Student\Runtime\Instances\Value\Value;
use IPP\Student\Runtime\Instances\ObjectInstance;
use IPP\Student\Runtime\Instances\Value\IntegerValue;
use IPP\Student\Runtime\Instances\Value\StringValue;
use IPP\Student\Runtime\Instances\Value\BooleanValue;
use IPP\Student\Runtime\Instances\Value\NilValue;
use IPP\Student\Runtime\Instances\BlockInstance;
use IPP\Student\Runtime\ClassRegistry;
use IPP\Student\Exception\UndefinedException;

class BuiltinConstructor
{
    public function __construct() {}

    /**
     * Constructs an instance of a built-in class using its constructor selector.
     * @param string $className
     * @param string $selector Either "new" or "from:"
     * @param array<int, Value> $args Arguments to the constructor
     * @return Value
     * @throws UndefinedException
     */
    public function constructBuiltinInstance(string $className, string $selector, array $args): Value
    {
        if ($selector === 'from:' && isset($args[0])) {
            return $this->handleFromSelector($className, $args);
        }

        if ($selector === 'new') {
            return $this->handleNewSelector($className);
        }

        throw new UndefinedException("Unknown class method '$selector' on class '$className'");
    }

    /**
     * Handles the "from:" selector for supported built-in types.
     * @param string $className
     * @param array<int, Value> $args
     * @return Value
     * @throws UndefinedException
     */
    private function handleFromSelector(string $className, array $args): Value
    {
        $value = $args[0];

        if ($className === 'Integer') {
            return $this->createInstance('Integer', 'value', $value);
        }

        if ($className === 'String') {
            return $this->createInstance('String', 'value', $value);
        }

        if ($className === 'Boolean') {
            return $this->createInstance($className, 'value', $value);
        }

        if ($className === 'True') {
            return $this->createInstance($className, 'value', BooleanValue::from(true));
        }

        if ($className === 'False') {
            return $this->createInstance($className, 'value', BooleanValue::from(false));
        }

        if ($className === 'Block') {
            if ($value instanceof BlockInstance) {
                return $this->createInstance('Block', 'value', $value);
            } else {
                throw new UndefinedException("Expected block in Block from:");
            }
        }

        throw new UndefinedException("Unknown class '$className' for from:");
    }

    /**
     * Handles the "new" selector for supported built-in types.
     * @param string $className
     * @return Value
     * @throws UndefinedException
     */
    private function handleNewSelector(string $className): Value
    {
        $registry = ClassRegistry::getInstance();

        if ($className === 'Nil') {
            return NilValue::get();
        }

        if ($className === 'Integer' || $className === 'String' || $className === 'Block') {
            return $this->createInstance($className, 'value', NilValue::get());
        }

        if ($className === 'True') {
            return $this->createInstance($className, 'value', BooleanValue::from(true));
        }
    
        if ($className === 'False') {
            return $this->createInstance($className, 'value', BooleanValue::from(false));
        }

        $classDef = $registry->get($className);
        return new ObjectInstance($classDef);
    }

    /**
     * Utility method for creating an ObjectInstance with one internal attribute.
     * @param string $className
     * @param string $attributeName
     * @param Value $value
     * @return ObjectInstance
     */
    private function createInstance(string $className, string $attributeName, Value $value): ObjectInstance
    {
        $registry = ClassRegistry::getInstance();
        $classDef = $registry->get($className);

        $instance = new ObjectInstance($classDef);
        $instance->setAttribute($attributeName, $value);

        return $instance;
    }
}
