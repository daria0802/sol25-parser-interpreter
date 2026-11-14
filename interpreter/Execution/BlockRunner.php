<?php
/**
 * BlockRunner – executes <block> elements 
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Execution;

use DOMElement;
use IPP\Core\Exception\IPPException;
use IPP\Core\Exception\InternalErrorException;
use IPP\Student\Runtime\Instances\Value\Value;
use IPP\Student\Runtime\Instances\Value\NilValue;
use IPP\Student\Evaluator\ExpressionEvaluator;
use IPP\Student\Evaluator\MessageDispatcher;
use IPP\Student\Runtime\ClassRegistry;

class BlockRunner
{
    /** @var ExpressionEvaluator Evaluator for expressions and message sends */
    private ExpressionEvaluator $evaluator;

    /**
     * Construct the block runner with an associated message dispatcher.
     * @param MessageDispatcher $dispatcher
     */
    public function __construct(MessageDispatcher $dispatcher)
    {
        $this->evaluator = new ExpressionEvaluator($dispatcher);
    }

    /**
     * Executes a block in the given context.
     * @param DOMElement $block The <block> element to run
     * @param ExecutionContext $context The current execution context
     * @return Value The result of the last evaluated expression or Nil
     * @throws IPPException|InternalErrorException
     */
    public function runBlock(DOMElement $block, ExecutionContext $context): Value
    {
        try {
            $result = null;

            foreach ($block->childNodes as $node) {
                if (!$node instanceof DOMElement) continue;
                if ($node->nodeName === 'parameter') continue;

                switch ($node->nodeName) {
                    case 'assign':
                        $result = $this->runAssignment($node, $context);
                        break;

                    case 'send':
                        $result = $this->evaluator->evaluateSend($node, $context);
                        break;

                    case 'return':
                        return $this->evaluator->evaluateExpression($node->firstElementChild, $context);

                    case 'expr':
                        $result = $this->evaluator->evaluateExpression($node->firstElementChild, $context);
                        break;

                    default:
                        throw new \IPP\Student\Exception\InvalidStructureException("Unknown block element: <{$node->nodeName}>");
                }
            }

            return $result ?? NilValue::get();
        } catch (IPPException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InternalErrorException("Internal error while executing block: " . $e->getMessage(), $e);
        }
    }

    /**
     * Handles assignment statements within a block.
     * @param DOMElement $assign The <assign> element
     * @param ExecutionContext $context The current execution context
     * @return Value The result of the evaluated expression
     * @throws IPPException|InternalErrorException
     */
    private function runAssignment(DOMElement $assign, ExecutionContext $context): Value
    {
        $varName = '';
        try {
            $varName = $assign->getElementsByTagName('var')->item(0)?->getAttribute('name');
            $exprElement = $assign->getElementsByTagName('expr')->item(0)?->firstElementChild;

            if (!$varName || !$exprElement) {
                throw new \IPP\Student\Exception\InvalidStructureException("Invalid <assign> syntax");
            }

            $value = $this->evaluator->evaluateExpression($exprElement, $context);
            // print_r("[DEBUG] assign: $varName := ");
            // var_dump($value);

            $context->getMemory()->set($varName, $value);
            return $value;
        } catch (IPPException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InternalErrorException("Internal error during assignment to variable '{$varName}': " . $e->getMessage(), $e);
        }
    }
}
