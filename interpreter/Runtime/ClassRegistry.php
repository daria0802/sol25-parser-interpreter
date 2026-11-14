<?php
/**
 * ClassRegistry – global registry of all class definitions in the SOL25 runtime.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Runtime;

class ClassRegistry
{
    /** @var array<string, ClassDef> Registered classes indexed by name */
    private array $classes = [];
    /** @var self|null Singleton instance of the registry */
    private static ?self $instance = null;

    /**
     * Register a new class definition.
     * @param ClassDef $class
     * @throws \IPP\Student\Exception\CollisionException If the class name is already registered
     */
    public function register(ClassDef $class): void
    {
        $name = $class->getName();
        if (isset($this->classes[$name])) {
            throw new \IPP\Student\Exception\CollisionException("Duplicate class '$name'");
        }
        $this->classes[$name] = $class;
    }

    /**
     * Check if a class is registered.
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->classes[$name]);
    }

    /**
     * Retrieve a registered class definition.
     * @param string $name
     * @return ClassDef
     * @throws \IPP\Student\Exception\UndefinedException If the class is not found
     */
    public function get(string $name): ClassDef
    {
        if (!isset($this->classes[$name])) {
            throw new \IPP\Student\Exception\UndefinedException("Class '$name' not found");
        }
        return $this->classes[$name];
    }

    /**
     * Get the singleton instance of the class registry.
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Set the singleton instance 
     * @param self $registry
     * @return void
     */
    public static function setInstance(self $registry): void
    {
        self::$instance = $registry;
    }
}
