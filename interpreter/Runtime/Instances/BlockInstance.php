<?php
/**
 * BlockInstance – represents a block object in the SOL25 runtime.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime\Instances;

use IPP\Student\Execution\Memory;
use IPP\Student\Runtime\Instances\Value\Value;
use DOMElement;

class BlockInstance implements Value
{
    /** @var list<array{order: int, name: string}> Block parameters with order and name */
    private array $params;
    /** @var DOMElement The XML node representing the block's body */
    private DOMElement $body;
    /** @var Memory Lexical scope captured at the block's definition */
    private Memory $closure;
    /** @var Value The self object at the time the block was created */
    private Value $self;


    /**
     * Create a new block instance.
     * @param list<array{order: int, name: string}> $params Block parameters
     * @param DOMElement $body XML representation of block code
     * @param Memory $closure Captured lexical environment
     * @param Value $self Self reference when block was defined
     */
    public function __construct(array $params, DOMElement $body, Memory $closure, Value $self)
    {
        $this->params = $params;
        $this->body = $body;
        $this->closure = $closure;
        $this->self = $self;
    }

    /**
     * Get the list of block parameters.
     * @return list<array{order: int, name: string}>
     */
    public function getParameters(): array
    {
        return $this->params;
    }

    /**
     * Get the DOMElement representing the block's body.
     * @return DOMElement
     */
    public function getBody(): DOMElement
    {
        return $this->body;
    }

    /**
     * Get the captured closure memory.
     * @return Memory
     */
    public function getClosure(): Memory
    {
        return $this->closure;
    }

    /**
     * Get the self reference for the block
     * @return Value
     */
    public function getSelf(): Value
    {
        return $this->self;
    }

    public function toString(): string
    {
        return "[block]";
    }

    /**
     * Chck identity with another value.
     * @param Value $other
     * @return bool True if comparing the same object reference
     */

    public function equals(Value $other): bool
    {
        return $this === $other;
    }

    /**
     * Return the runtime class name for block instances.
     * @return string
     */
    public function getClassName(): string
    {
        return "Block";
    }
}
