<?php
/**
 * NilValue – represents the singleton nil value in SOL25.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances\Value;

class NilValue implements Value
{
    /** @var NilValue The single instance of NilValue */
    private static NilValue $instance; 

    private function __construct() {}

    /**
     * Get the instance of NilValue.
     * @return NilValue
     */
    public static function get(): NilValue
    {
        return self::$instance ??= new self();
    }

    /**
     * Get the string representation of nil.
     * @return string
     */
    public function toString(): string
    {
        return 'nil';
    }

    public function equals(Value $other): bool
    {
        return $other instanceof NilValue;
    }

    /**
     * Get the runtime class name of this value.
     * @return string
     */
    public function getClassName(): string
    {
        return 'Nil';
    }
}
