<?php
/**
 * ObjectInstance – represents an instance of a user-defined class in SOL25
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances;

use IPP\Student\Runtime\Instances\Value\Value;
use IPP\Student\Runtime\ClassDef;

class ObjectInstance implements Value
{
    /** @var ClassDef Reference to the class definition */
    private ClassDef $classDef;
    /** @var array<string, Value> Map of attribute names to their values */
    private $attributes = [];

    /**
     * Create a new object instance from the given class definition.
     * @param ClassDef $classDef
     */
    public function __construct(ClassDef $classDef)
    {
        $this->classDef = $classDef;
    }

    /**
     * Set the value of an attribute.
     * @param string $name
     * @param Value $value
     */
    public function setAttribute(string $name, Value $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Get the value of an attribute. Throws if the attribute is undefined.
     * @param string $name
     * @return Value
     * @throws \IPP\Student\Exception\UndefinedException
     */
    public function getAttribute(string $name): Value
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new \IPP\Student\Exception\UndefinedException("Attribute '$name' not found in object of class '{$this->classDef->getName()}'");
        }
        return $this->attributes[$name];
    }

    /**
     * Check if an attribute is defined on this object.
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Get all defined attribute names.
     * @return string[]
     */
    public function getAttributeNames(): array
    {
        return array_keys($this->attributes);
    }

    /**
     * Get the class definition this object is an instance of.
     * @return ClassDef
     */
    public function getClassDef(): ClassDef
    {
        return $this->classDef;
    }

    public function toString(): string
    {
        return "<Instance of " . $this->classDef->getName() . ">";
    }

    /**
     * Check if another value is the same object instance.
     * @param Value $other
     * @return bool
     */
    public function equals(Value $other): bool
    {
        return $this === $other;
    }

    /**
     * Return the class name of this instance.
     * @return string
     */
    public function getClassName(): string
    {
        return $this->classDef->getName();
    }
}
