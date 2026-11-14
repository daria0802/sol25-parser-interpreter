<?php
/**
 * ExecutionContext – represents the context in which a SOL25 block or method is executed.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Execution;

use IPP\Student\Runtime\Instances\Value\Value;

class ExecutionContext
{
    /** @var Value Reference to the current `self` object */
    private Value $self;
    /** @var Memory Variable scope associated with this context */
    private Memory $memory;

    /**
     * Construct a new execution context with a self object and memory scope.
     * @param Value $self
     * @param Memory $memory
     */
    public function __construct(Value $self, Memory $memory)
    {
        $this->self = $self;
        $this->memory = $memory;
    }

    /**
     * Get the current `self` reference.
     * @return Value
     */
    public function getSelf(): Value
    {
        return $this->self;
    }

    /**
     * Get the memory (scope) associated with this context.
     * @return Memory
     */
    public function getMemory(): Memory
    {
        return $this->memory;
    }
}
