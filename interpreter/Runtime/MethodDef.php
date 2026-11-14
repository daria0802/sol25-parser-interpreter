<?php
namespace IPP\Student\Runtime;
/**
 * MethodDef – represents an instance method defined in a SOL25 class.
 * @author Daria Kinash (xkinas00)
 */
use DOMElement;

use IPP\Core\Exception\IPPException;
use IPP\Core\Exception\InternalErrorException;
use IPP\Student\Evaluator\MessageDispatcher;
use IPP\Student\Execution\BlockRunner;
use IPP\Student\Execution\ExecutionContext;
use IPP\Student\Runtime\Instances\Value\Value;
use IPP\Student\Runtime\Instances\Value\NilValue;

class MethodDef
{
    /** @var string Method selector */
    private string $selector;
    /** @var DOMElement XML block element containing the method body */
    private DOMElement $block;

    /**
     * Construct a method definition from its selector and XML element.
     * @param string $selector
     * @param DOMElement $methodElement
     * @throws \IPP\Student\Exception\InvalidStructureException
     */
    public function __construct(string $selector, DOMElement $methodElement)
    {
        $this->selector = $selector;
        $block = $methodElement->getElementsByTagName('block')->item(0);
        if (!$block instanceof DOMElement) {
            throw new \IPP\Student\Exception\InvalidStructureException("Method '$selector' does not contain a valid <block>");
        }

        $this->block = $block;
    }

    /**
     * Get the method selector.
     * @return string
     */
    public function getSelector(): string
    {
        return $this->selector;
    }

    /**
     * Get the DOM element representing the method's block.
     * @return DOMElement
     */
    public function getBlock(): DOMElement
    {
        return $this->block;
    }

    /**
     * Get the method's parameter list.
     * @return list<array{order: int, name: string}>
     */
    public function getParameterList(): array
    {
        $parameters = [];
        foreach ($this->block->getElementsByTagName('parameter') as $param) {
            $order = (int) $param->getAttribute('order');
            $name = $param->getAttribute('name');

            $parameters[] = ['order' => $order, 'name' => $name];
        }

        usort($parameters, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $parameters;
    }

    /**
     * Invoke the method in a given context with arguments.
     * @param array<Value> $args Arguments to bind to parameters
     * @param ExecutionContext $context Current execution context
     * @param MessageDispatcher $dispatcher Dispatcher for message sending
     * @return Value Result of method execution
     * @throws IPPException|InternalErrorException
     */
    public function invoke(array $args, ExecutionContext $context, MessageDispatcher $dispatcher): Value
    {
        try {
            $parameters = $this->getParameterList();
            $memory = $context->getMemory();

            foreach ($parameters as $index => $param) {
                $memory->set($param['name'], $args[$index] ?? NilValue::get());
            }

            $blockRunner = new BlockRunner($dispatcher);
            return $blockRunner->runBlock($this->block, $context);
        } catch (IPPException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InternalErrorException("Internal error while invoking method '{$this->selector}': " . $e->getMessage(), $e);
        }
    }

    /**
     * Get the number of parameters the method expects.
     * @return int
     */
    public function getArity(): int
    {
        return (int) $this->block->getAttribute('arity');
    }
}
