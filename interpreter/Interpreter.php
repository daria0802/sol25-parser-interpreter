<?php
/**
 * Implementation of the main interpreter class for the SOL25 language.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\IPPException;
use IPP\Student\Execution\ProgramRunner;
use IPP\Student\Runtime\ClassRegistry;
use IPP\Student\Evaluator\MessageDispatcher;
use IPP\Student\Evaluator\BuiltinMethodHandler;
use IPP\Student\Evaluator\BuiltinConstructor;
use IPP\Student\Execution\BlockRunner;


class Interpreter extends AbstractInterpreter
{
    /**
     * Main method that sets up and runs the interpreter.
     * @return int Exit code (0 on success)
     * @throws IPPException If an interpreter error occurs
     */
    public function execute(): int
    {
        try {
            $dom = $this->source->getDOMDocument();
            $classRegistry = new ClassRegistry();
            ClassRegistry::setInstance($classRegistry);
            $builtinMethodHandler = new BuiltinMethodHandler($this->stdout, $this->input);
            $BuiltinConstructor = new BuiltinConstructor();
            $dispatcher = new MessageDispatcher($classRegistry, $builtinMethodHandler, $BuiltinConstructor);
            $blockRunner = new BlockRunner($dispatcher);
            $builtinMethodHandler->setBlockRunner($blockRunner);
            $runner = new ProgramRunner($dom, $classRegistry, $dispatcher);
            $runner->prepare();
            $runner->run();
        } catch (IPPException $e) {
            throw $e;
        }

        return 0;
    }
}

