<?php
/**
 * Memory – represents a variable scope in the SOL25 runtime.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Execution;

use IPP\Student\Runtime\Instances\Value\Value;

class Memory
{
    /** @var array<string, Value> Map of variable names to their values */
    private array $variables = [];
    /** @var Memory|null Reference to the parent scope */
    private ?Memory $parent;

    /**
     * Create a new memory scope with optional parent scope.
     * @param Memory|null $parent
     */
    public function __construct(?Memory $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Define or overwrite a variable in the current scope.
     * @param string $name
     * @param Value $value
     */
    public function set(string $name, Value $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * Get the value of a variable
     * @param string $name
     * @return Value
     * @throws \IPP\Student\Exception\UndefinedException If variable is not found
     */
    public function get(string $name): Value
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        if ($this->parent !== null) {
            return $this->parent->get($name);
        }

        throw new \IPP\Student\Exception\UndefinedException("Variable '$name' not found");
    }

    /**
     * Check if a variable is defined in this scope or a parent scope.
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        if (array_key_exists($name, $this->variables)) {
            return true;
        }

        if ($this->parent !== null) {
            return $this->parent->has($name);
        }

        return false;
    }
}
