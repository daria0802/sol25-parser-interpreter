<?php
/**
 * StringValue – represents a string value in the SOL25 language.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances\Value;

class StringValue implements Value
{
    /** @var string The string value */
    private string $value;

    /**
     * Constructor to initialize the string value.
     * @param string $value The string value
     */
    public function __construct(string $value) {
        $this->value = $value;
    }

    /**
     * Get the string value
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Convert the value to its string representation
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another value
     * @param Value $other
     * @return bool True if both are StringValue and contain the same string
     */
    public function equals(Value $other): bool
    {
        return $other instanceof StringValue && $other->value === $this->value;
    }

    /**
     * Get the runtime class name of this value
     * @return string
     */
    public function getClassName(): string
    {
        return 'String';
    }
}
