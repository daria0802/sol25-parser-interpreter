<?php

namespace IPP\Student\Evaluator;
/**
 * MessageDispatcher – handles method dispatch in the SOL25 interpreter.
 * @author Daria Kinash (xkinas00)
 */
use IPP\Core\Exception\IPPException;
use IPP\Core\Exception\InternalErrorException;
use IPP\Student\Exception\UndefinedException;
use IPP\Student\Runtime\ClassRegistry;
use IPP\Student\Runtime\ClassDef;
use IPP\Student\Runtime\MethodDef;
use IPP\Student\Runtime\Instances\Value\Value;
use IPP\Student\Runtime\Instances\Value\StringValue;
use IPP\Student\Runtime\Instances\Value\IntegerValue;
use IPP\Student\Runtime\Instances\Value\BooleanValue;
use IPP\Student\Runtime\Instances\ObjectInstance;
use IPP\Student\Runtime\Instances\Value\ClassReference;
use IPP\Student\Execution\ExecutionContext;
use IPP\Student\Execution\Memory;

class MessageDispatcher
{
    private ClassRegistry $classRegistry;
    private BuiltinMethodHandler $builtinHandler;
    private BuiltinConstructor $builtinConstructor;

    /**
     * Initialize the dispatcher with registry, built-in method handler, and constructor logic.
     */
    public function __construct(
        ClassRegistry $classRegistry,
        BuiltinMethodHandler $builtinHandler,
        BuiltinConstructor $builtinConstructor
    ) {
        $this->classRegistry = $classRegistry;
        $this->builtinHandler = $builtinHandler;
        $this->builtinConstructor = $builtinConstructor;
    }

    /**
     * Dispatch a message to a value (object or class reference).
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value
     * @throws IPPException
     */
    public function dispatch(Value $receiver, string $selector, array $args): Value
    {
        try {
            if ($receiver instanceof ClassReference) {
                return $this->dispatchClassReference($receiver, $selector, $args);
            }
    
            if ($receiver instanceof ObjectInstance) {
                return $this->dispatchInstance($receiver, $selector, $args);
            }
    
            return $this->builtinHandler->handle($receiver, $selector, $args);
    
        } catch (IPPException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InternalErrorException(
                "Internal error during message dispatch to selector '{$selector}' on receiver '" . $receiver->toString() . "': " . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Dispatch a method call to an object instance
     * @param ObjectInstance $receiver
     * @param string $selector
     * @param array<Value> $args
     * @return Value
     */
    private function dispatchInstance(ObjectInstance $receiver, string $selector, array $args): Value
    {
        try{
            $classDef = $receiver->getClassDef();
            $methodDef = $this->findMethod($classDef, $selector);

            if ($methodDef !== null) {
                $context = new ExecutionContext($receiver, new Memory());
                return $methodDef->invoke($args, $context, $this);
            }

            return $this->builtinHandler->handle($receiver, $selector, $args);
        } catch (IPPException $e) {
            throw $e;
        }
    }

    /**
     * Dispatch a method call to a class reference or handle construction.
     * @param ClassReference $receiver
     * @param string $selector
     * @param array<Value> $args
     * @return Value
     * @throws UndefinedException
     */
    private function dispatchClassReference(ClassReference $receiver, string $selector, array $args): Value
    {
        $className = $receiver->getValue();

        if (!$this->classRegistry->has($className)) {
            throw new UndefinedException("Unknown class '$className'");
        }

        $classDef = $this->classRegistry->get($className);
    

        $methodDef = $this->findMethod($classDef, $selector);
        if ($methodDef !== null) {
            $instance = $this->createEmptyInstance($className, $classDef);
            $context = new ExecutionContext($instance, new Memory());
            return $methodDef->invoke($args, $context, $this);
        }

        if (in_array($selector, ['new', 'from:'])) {
            if (in_array($className, ['Integer', 'String', 'Boolean', 'True', 'False', 'Nil', 'Block'])) {
                return $this->builtinConstructor->constructBuiltinInstance($className, $selector, $args);
            }
        }

        if ($selector === 'new') {
            return new ObjectInstance($classDef);
        }

        if ($selector === 'from:' && isset($args[0])) {
            return $this->handleFromConstructor($classDef, $args[0]);
        }

        $builtin = $this->findBuiltinParent($classDef);
        if ($builtin !== null) {
            $instance = $this->createEmptyInstance($builtin, $classDef);
            return $this->builtinHandler->handle($instance, $selector, $args);
        }

        return $this->builtinHandler->handle($receiver, $selector, $args);
    }

    /**
     * Reursively search for a method in the class hierarchy.
     * @param ClassDef $classDef
     * @param string $selector
     * @return MethodDef|null
     */
    private function findMethod(ClassDef $classDef, string $selector): ?MethodDef
    {
        while ($classDef !== null) {
            if ($classDef->hasMethod($selector)) {
                return $classDef->getMethod($selector);
            }
            $classDef = $classDef->getParent();
        }
        return null;
    }

    /**
     * Create a default instance for a given built-in or user-defined class.
     * @param string $className
     * @param ClassDef $classDef
     * @return Value
     */
    private function createEmptyInstance(string $className, ClassDef $classDef): Value
    {
        if ($className === 'Integer') {
            return new IntegerValue(0);
        } elseif ($className === 'String') {
            return new StringValue("");
        } elseif ($className === 'True') {
            return BooleanValue::from(true);
        } elseif ($className === 'False') {
            return BooleanValue::from(false);
        } else {
            return new ObjectInstance($classDef);
        }
    }

    /**
     * Handles copying attributes for `from:` constructor.
     * @param ClassDef $classDef
     * @param Value $arg
     * @return ObjectInstance
     */
    private function handleFromConstructor(ClassDef $classDef, Value $arg): ObjectInstance
    {
        $copy = new ObjectInstance($classDef);

        if ($arg instanceof ObjectInstance) {
            foreach ($arg->getAttributeNames() as $attrName) {
                $copy->setAttribute($attrName, $arg->getAttribute($attrName));
            }
        } else {
            $copy->setAttribute('value', $arg);
        }

        return $copy;
    }

    /**
     * Recursively find a known built-in parent class name.
     * @param ClassDef $classDef
     * @return string|null
     */
    private function findBuiltinParent(ClassDef $classDef): ?string
    {
        while ($classDef !== null) {
            $name = $classDef->getName();
            if (in_array($name, ['Integer', 'String', 'Boolean', 'True', 'False', 'Nil', 'Block'])) {
                return $name;
            }
            $classDef = $classDef->getParent();
        }
        return null;
    }
}
