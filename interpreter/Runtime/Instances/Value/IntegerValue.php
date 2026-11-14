<?php
/**
 * IntegerValue – represents an integer value in the SOL25 language.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances\Value;

class IntegerValue implements Value{
    /** @var int The integer value */
    private int $value;

    /**
     * Create a new IntegerValue.
     * @param int $value The integer value
     */
    public function __construct(int $value) {
        $this->value = $value;
    }

    /**
     * Get the integer value
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Convert the integer value to a string.
     * @return string
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Check if another value is an IntegerValue with the same number
     * @param Value $other
     * @return bool
     */
    public function equals(Value $other): bool
    {
        return $other instanceof IntegerValue && $other->value === $this->value;
    }

    /**
     * Return the class name of this value.
     * @return string
     */
    public function getClassName(): string
    {
        return 'Integer';
    }

}