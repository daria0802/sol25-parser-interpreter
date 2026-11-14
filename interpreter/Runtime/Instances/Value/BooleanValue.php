<?php
/**
 * BooleanValue class – represents a boolean value (true/false).
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances\Value;

class BooleanValue implements Value
{
     /** @var bool Boolean value */
    private bool $value;

    /**
     * Constructor to initialize the boolean value.
     * @param bool $value True or False value
     */
    public function __construct(bool $value) {
        $this->value = $value;
    }

    /**
     * Method to create a BooleanValue.
     * @param bool $value Value 
     * @return BooleanValue
     */
    public static function from(bool $value): BooleanValue
    {
        return new BooleanValue($value);
    }

    /**
     * Get the boolean value.
     * @return bool
     */
    public function getValue(): bool
    {
        return $this->value;
    }

    /**
     * Convert the value to string representation.
     * @return string 'true' or 'false'
     */
    public function toString(): string
    {
        return $this->value ? 'true' : 'false';
    }

    /**
     * Compare this value with another for equality.
     * @param Value $other Another value to compare with
     * @return bool True if both are BooleanValue and have the same boolean value
     */
    public function equals(Value $other): bool
    {
        return $other instanceof BooleanValue && $other->value === $this->value;
    }

    /**
     * Get the class name for this boolean value.
     * @return string 'True' or 'False'
     */
    public function getClassName(): string
    {
        return $this->value ? 'True' : 'False';
    }
}
