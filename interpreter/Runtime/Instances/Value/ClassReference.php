<?php
/**
 * ClassReference – represents a reference to a class in SOL25.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances\Value;

class ClassReference implements Value
{
    /** @var string The name of the class being referenced */
    private string $className;

    /**
     * Constructor to initialize the reference with a class name.
     * @param string $className Name of the class
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return 'ClassReference';
    }

     /**
     * Get the actual name of the class being referenced.
     * @return string
     */
    public function getValue(): string
    {
        return $this->className;
    }

    /**
     * Compare this value with another value for equality.
     * @param Value $other The other value to compare
     * @return bool True if both are ClassReference and refer to the same class
     */
    public function equals(Value $other): bool
    {
        return $other instanceof self && $this->className === $other->className;
    }

    /**
     * Return a string representation
     * @return string
     */
    public function toString(): string
    {
        return "<ClassRef {$this->className}>";
    }
}
