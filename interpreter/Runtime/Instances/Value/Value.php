<?php
/**
 * Value interface – defines the behavior of all runtime values in SOL25.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances\Value;

interface Value
{
    public function toString(): string;
    public function equals(Value $other): bool;
    public function getClassName(): string;
}
