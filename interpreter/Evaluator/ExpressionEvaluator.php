<?php
/**
 * ExpressionEvaluator – evaluates XML-based expressions in the SOL25 language.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Evaluator;

use DOMElement;
use IPP\Core\Exception\IPPException;
use IPP\Student\Execution\ExecutionContext;
use IPP\Student\Runtime\Instances\Value\Value;
use IPP\Student\Runtime\Instances\Value\BooleanValue;
use IPP\Student\Runtime\Instances\Value\IntegerValue;
use IPP\Student\Runtime\Instances\Value\StringValue;
use IPP\Student\Runtime\Instances\Value\NilValue;
use IPP\Student\Runtime\Instances\BlockInstance;
use IPP\Student\Runtime\ClassRegistry;
use \IPP\Student\Runtime\Instances\Value\ClassReference;
use IPP\Student\Runtime\Instances\ObjectInstance;

class ExpressionEvaluator
{
    private MessageDispatcher $dispatcher;

    /**
     * Constructor with MessageDispatcher 
     * @param MessageDispatcher $dispatcher
     */
    public function __construct(MessageDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Evaluates any expression node recursively.
     * @param DOMElement|null $expr
     * @param ExecutionContext $context
     * @return Value
     */
    public function evaluateExpression(?DOMElement $expr, ExecutionContext $context): Value
    {
        if (!$expr) {
            return NilValue::get();
        }

        try {
            if ($expr->nodeName === 'expr') {
                return $this->evaluateExpression($expr->firstElementChild, $context);
            }

            if ($expr->nodeName === 'literal') {
                return $this->evaluateLiteral($expr);
            } elseif ($expr->nodeName === 'var') {
                return $this->evaluateVariable($expr, $context);
            } elseif ($expr->nodeName === 'send') {
                return $this->evaluateSend($expr, $context);
            } elseif ($expr->nodeName === 'block') {
                return $this->evaluateBlock($expr, $context);
            } else {
                throw new \IPP\Student\Exception\InvalidStructureException("Unknown expression type <{$expr->nodeName}>");
            }
        } catch (IPPException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error while evaluating expression: " . $e->getMessage(), $e
            );
        }
    }

    /**
     * Evaluates a <literal> element.
     * @param DOMElement $node
     * @return Value
     */
    private function evaluateLiteral(DOMElement $node): Value
    {
        $class = $node->getAttribute('class');
        $value = $node->getAttribute('value');

        if ($class === 'Integer') {
            return new IntegerValue((int)$value);
        } elseif ($class === 'String') {
            return new StringValue($value);
        } elseif ($class === 'True') {
            return BooleanValue::from(true);
        } elseif ($class === 'False') {
            return BooleanValue::from(false);
        } elseif ($class === 'Nil') {
            return NilValue::get();
        } elseif ($class === 'class') {
            return $this->createClassReference($value);
        } else {
            throw new \IPP\Student\Exception\DoesNotUnderstandException("Unknown literal class '$class'");
        }
        
    }

    /**
     * Creates a ClassReference from class name
     * @param string $className
     * @return Value
     */
    private function createClassReference(string $className): Value
    {
        if (!ClassRegistry::getInstance()->has($className)) {
            throw new \IPP\Student\Exception\UndefinedException("Class '$className' not found");
        }

        return new ClassReference($className);
    }

    /**
     * Evaluates a <var> element
     * @param DOMElement $node
     * @param ExecutionContext $context
     * @return Value
     */
    private function evaluateVariable(DOMElement $node, ExecutionContext $context): Value
    {
        try {
            $name = $node->getAttribute('name');
            if ($name === 'self') {
                return $context->getSelf(); 
            }

            return $context->getMemory()->get($name);
        } catch (\IPP\Core\Exception\IPPException $e) {
            throw $e; 
        } catch (\Throwable $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error while evaluating variable: " . $e->getMessage(), $e);
        }
    }

    /**
     * Evaluates a <send> element, including selector dispatching
     * @param DOMElement $node
     * @param ExecutionContext $context
     * @return Value
     */
    public function evaluateSend(DOMElement $node, ExecutionContext $context): Value
    {
        try {
            $selector = $node->getAttribute('selector');
            $receiverNode = null;
            $args = [];

            foreach ($node->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }

                if ($child->tagName === 'expr' && $receiverNode === null) {
                    $receiverNode = $child;
                } elseif ($child->tagName === 'arg') {
                    $order = (int) $child->getAttribute('order');
                    $valueExpr = $child->getElementsByTagName('expr')->item(0)?->firstElementChild;
                    $args[$order - 1] = $this->evaluateExpression($valueExpr, $context);
                }
            }

            $receiver = $this->evaluateExpression($receiverNode, $context);

            ksort($args); 

            try {
                return $this->dispatcher->dispatch($receiver, $selector, $args);
            } catch (\IPP\Core\Exception\IPPException $e) {
                if ($receiver instanceof ObjectInstance && $receiver === $context->getSelf()) {
                    if (substr_count($selector, ':') === 1 && count($args) === 1) {
                        $attrName = rtrim($selector, ':');
                        $receiver->setAttribute($attrName, $args[0]);
                        return $args[0];
                    }

                    if (!str_contains($selector, ':') && count($args) === 0 && $receiver->hasAttribute($selector)) {
                        return $receiver->getAttribute($selector);
                    } 
                    else {
                        throw new \IPP\Student\Exception\DoesNotUnderstandException("Unknown attribute '$selector' on self");
                    }
                }
                throw $e; 
            }
        } catch (IPPException $e) {
            throw $e; 
        } catch (\Throwable $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error while evaluating 'send' expression: " . $e->getMessage(), $e
            );
        }
    }

    /**
     * Creates a BlockInstance from a <block> node.
     * @param DOMElement $node
     * @param ExecutionContext $context
     * @return Value
     */
    private function evaluateBlock(DOMElement $node, ExecutionContext $context): Value
    {
        $params = $this->extractParams($node);
        return new BlockInstance($params, $node, $context->getMemory(), $context->getSelf());
    }

    /**
     * Extracts parameters from a block element
     * @param DOMElement $block
     * @return list<array{order: int, name: string}>
     */
    private function extractParams(DOMElement $block): array
    {
        $params = [];

        foreach ($block->getElementsByTagName('parameter') as $param) {

            $params[] = [
                'order' => (int)$param->getAttribute('order'),
                'name'  => $param->getAttribute('name'),
            ];
        }

        usort($params, fn($a, $b) => $a['order'] <=> $b['order']);

        return $params;
    }

}
