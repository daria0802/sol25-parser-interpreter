<?php
/**
 * ProgramRunner – coordinates the preparation and execution of a SOL25 program.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Execution;

use DOMDocument;
use DOMElement;
use IPP\Core\Exception\IPPException;
use IPP\Core\Exception\XMLException;
use IPP\Student\Exception\MissingMainException;
use IPP\Student\Runtime\ClassRegistry;
use IPP\Student\Runtime\ClassDef;
use IPP\Student\Runtime\Instances\ObjectInstance;
use IPP\Student\Runtime\MethodDef;
use IPP\Student\Execution\Memory;
use IPP\Student\Execution\ExecutionContext;
use IPP\Student\Evaluator\MessageDispatcher;

class ProgramRunner
{
    private DOMDocument $xml;
    private ClassRegistry $classRegistry;
    private MessageDispatcher $dispatcher;

    /**
     * Constructor for ProgramRunner.
     * @param DOMDocument $xml Parsed XML AST of the program
     * @param ClassRegistry $classRegistry Global class registry
     * @param MessageDispatcher $dispatcher Dispatcher for sending messages
     */
    public function __construct(
        DOMDocument $xml,
        ClassRegistry $classRegistry,
        MessageDispatcher $dispatcher
    ) {
        $this->xml = $xml;
        $this->classRegistry = $classRegistry;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Parses the XML AST and registers all classes.
     * @throws IPPException|XMLException|\IPP\Core\Exception\InternalErrorException
     */
    public function prepare(): void
    {
        try {
            $program = $this->xml->documentElement;
            if ($program === null || $program->nodeName !== 'program') {
                throw new XMLException("Missing <program> root element");
            }

            $this->registerBuiltinClasses();

            $pendingClasses = [];

            foreach ($program->getElementsByTagName('class') as $classElement) {
                $name = $classElement->getAttribute('name');
                $parentName = $classElement->getAttribute('parent') ?: null;

                $methods = [];
                foreach ($classElement->getElementsByTagName('method') as $methodElement) {
                    $selector = $methodElement->getAttribute('selector');
                    $methods[$selector] = new MethodDef($selector, $methodElement);
                }

                $pendingClasses[] = [$name, $parentName, $methods];
            }

            foreach ($pendingClasses as [$name, $parentName, $methods]) {
                $parentDef = $parentName ? $this->classRegistry->get($parentName) : null;
                $classDef = new ClassDef($name, $parentDef, $methods);
                $this->classRegistry->register($classDef);
            }
        } catch (IPPException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error during preparation: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Registers built-in SOL25 classes 
     */
    private function registerBuiltinClasses(): void
    {
        $objectClass = new ClassDef("Object", null, []);
        $this->classRegistry->register($objectClass);

        foreach (["String", "Integer", "Nil", "True", "False", "Block"] as $name) {
            $classDef = new ClassDef($name, $objectClass, []);
            $this->classRegistry->register($classDef);
        }
    }

    /**
     * Runs the program by invoking the `run` method of the `Main` class
     * @throws IPPException|MissingMainException|\IPP\Core\Exception\InternalErrorException
     */
    public function run(): void
    {
        try {
            if (!$this->classRegistry->has('Main')) {
                throw new \IPP\Student\Exception\MissingMainException("Missing class 'Main'");
            }

            $mainClass = $this->classRegistry->get('Main');

            if (!$mainClass->hasMethod('run')) {
                throw new MissingMainException("Main run method not found");
            }

            $mainInstance = new ObjectInstance($mainClass);
            $runMethod = $mainClass->getMethod('run');
            $memory = new Memory();
            $context = new ExecutionContext($mainInstance, $memory);
            $runMethod->invoke([], $context, $this->dispatcher);

        } catch (IPPException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error during execution: " . $e->getMessage(),
                previous: $e
            );
        }
    }
}
