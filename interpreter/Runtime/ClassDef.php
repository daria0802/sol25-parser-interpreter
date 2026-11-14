<?php
/**
 * ClassDef – represents a class definition in the SOL25 runtime.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime;

use IPP\Student\Exception\DoesNotUnderstandException;

class ClassDef
{
    /** @var string Name of the class */
    private string $name;
    /** @var ClassDef|null Reference to the parent class */
    private ?ClassDef $parent;
    /** @var array<string, MethodDef> Map of selector names to method definitions */
    private array $methods;

    /**
     * ClassDef constructor.
     * @param string $name Name of the class
     * @param ClassDef|null $parent Optional parent class
     * @param array<string, MethodDef> $methods Methods defined in this class
     */
    public function __construct(string $name, ?ClassDef $parent, array $methods = [])
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->methods = $methods;
    }

    /**
     * Get the parent class definition.
     * @return ClassDef|null
     */
    public function getParent(): ?ClassDef
    {
        return $this->parent;
    }

    /**
     * Get the name of this class.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the name of the parent class
     * @return string|null
     */
    public function getParentName(): ?string
    {
        return $this->parent?->getName();
    }

    /**
     * Check if the class defines a method for the given selector.
     * @param string $selector Method selector name
     * @return bool
     */
    public function hasMethod(string $selector): bool
    {
        return isset($this->methods[$selector]);
    }

    /**
     * Get the method definition for a given selector.
     * @param string $selector
     * @return MethodDef
     * @throws DoesNotUnderstandException If the method is not defined
     */
    public function getMethod(string $selector): MethodDef
    {
        if (!$this->hasMethod($selector)) {
            throw new DoesNotUnderstandException("Class '{$this->name}' does not understand selector '$selector'");
        }
        return $this->methods[$selector];
    }
}
