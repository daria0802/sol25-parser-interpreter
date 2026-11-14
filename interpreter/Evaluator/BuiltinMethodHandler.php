<?php
/**
 * BuiltinMethodHandler – evaluates built-in methods for SOL25 values.
 * @author Daria Kinash (xkinas00)
 */
namespace IPP\Student\Evaluator;

use DOMElement;
use IPP\Student\Runtime\Instances\Value\Value;
use IPP\Student\Runtime\Instances\Value\IntegerValue;
use IPP\Student\Runtime\Instances\Value\StringValue;
use IPP\Student\Runtime\Instances\Value\BooleanValue;
use IPP\Student\Runtime\Instances\Value\NilValue;
use IPP\Student\Runtime\Instances\BlockInstance;
use IPP\Student\Execution\BlockRunner;
use IPP\Student\Execution\ExecutionContext;
use IPP\Student\Execution\Memory;
use IPP\Student\Runtime\Instances\ObjectInstance;
use IPP\Core\Interface\OutputWriter;
use IPP\Core\Interface\InputReader;
use \IPP\Student\Exception\DoesNotUnderstandException;
use \IPP\Core\Exception\IPPException;

class BuiltinMethodHandler
{
    private ?BlockRunner $blockRunner = null;
    private OutputWriter $stdout;
    private InputReader $input;

    public function __construct(OutputWriter $stdout, InputReader $input)
    {
        $this->stdout = $stdout;
        $this->input = $input;
    }

    /**
     * Assign a block runner for executing block instances.
     */
    public function setBlockRunner(BlockRunner $runner): void
    {
        $this->blockRunner = $runner;
    }

    /**
     * Handle built-in methods by class type.
     * 
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value
     * @throws IPPException
     */
    public function handle(Value $receiver, string $selector, array $args): Value
    {
        try {
            $unwrappedReceiver = $this->unwrap($receiver);
            $class = $unwrappedReceiver->getClassName();

            if ($class === 'Integer') {
                return $this->handleInteger($unwrappedReceiver, $selector, $args);
            }

            if ($class === 'String') {
                return $this->handleString($unwrappedReceiver, $selector, $args);
            }

            if ($class === 'Block') {
                return $this->handleBlock($receiver, $selector, $args);
            }

            if ($class === 'True' || $class === 'False') {
                return $this->handleBoolean($unwrappedReceiver, $selector, $args);
            }

            if ($class === 'Nil') {
                return $this->handleNil($receiver, $selector, $args);
            }

            return $this->handleObjectMethods($unwrappedReceiver, $selector, $args)
                ?? throw new \IPP\Student\Exception\UndefinedException("Unknown built-in for class '$class' and selector '$selector'");
        } catch (IPPException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error while handling built-in method '$selector' on class '" . $receiver->getClassName() . "': " . $e->getMessage(), $e
            );
        }
    }

    /**
     * Handle base object methods.
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value|null
     */
    private function handleObjectMethods(Value $receiver, string $selector, array $args): ?Value
    {

        if ($selector === 'identicalTo:') {
            return BooleanValue::from($receiver === ($args[0]));
        }

        if ($selector === 'equalTo:') {
            return BooleanValue::from($receiver->equals($args[0]));
        }

        if ($selector === 'asString') {
            return new StringValue('');
        }

        if ($selector === 'isNumber') {
            return BooleanValue::from(false);
        }

        if ($selector === 'isString') {
            return BooleanValue::from(false);
        }

        if ($selector === 'isBlock') {
            return BooleanValue::from(false);
        }

        if ($selector === 'isNil') {
            return BooleanValue::from(false);
        }

        return null; 
    }

    /**
     * Handle Integer methods 
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value
     * @throws IPPException
     */
    private function handleInteger(Value $receiver, string $selector, array $args): Value
    {
        if (!($receiver instanceof IntegerValue)) {
            throw new \IPP\Student\Exception\TypeErrorException("Expected Integer receiver");
        }
        $a = $receiver->getValue();

        if ($selector === 'asString') {
            return new StringValue((string)$a);
        }

        if ($selector === 'timesRepeat:') {
            return $this->runTimesRepeat($a, $args[0] ?? null);
        }

        if ($selector === 'plus:') {
            $b_unwrap = isset($args[0]) ? $this->unwrap($args[0]) : null;

            if (!($b_unwrap instanceof IntegerValue)) {
                throw new \IPP\Student\Exception\ValueErrorException("Expected Integer argument");   
            }

            $b = $b_unwrap->getValue();
            return new IntegerValue($a + $b);
        }

        if ($selector === 'minus:') {
            $b_unwrap = isset($args[0]) ? $this->unwrap($args[0]) : null;

            if (!($b_unwrap instanceof IntegerValue)) {
                throw new \IPP\Student\Exception\ValueErrorException("Expected Integer argument");   
            }

            $b = $b_unwrap->getValue();
            return new IntegerValue($a - $b);
        }

        if ($selector === 'multiplyBy:') {
            $b_unwrap = isset($args[0]) ? $this->unwrap($args[0]) : null;

            if (!($b_unwrap instanceof IntegerValue)) {
                throw new \IPP\Student\Exception\ValueErrorException("Expected Integer argument");   
            }

            $b = $b_unwrap->getValue();
            return new IntegerValue($a * $b);
        }

        if ($selector === 'divBy:') {
            $b_unwrap = isset($args[0]) ? $this->unwrap($args[0]) : null;

            if (!($b_unwrap instanceof IntegerValue)) {
                throw new \IPP\Student\Exception\ValueErrorException("Expected Integer argument");   
            }

            $b = $b_unwrap->getValue();

            if ($b === 0) {
                throw new \IPP\Student\Exception\ValueErrorException("Division by zero");
            }
            return new IntegerValue((int)($a / $b));
        }

        if ($selector === 'asInteger') {
            return $receiver;
        }

        if ($selector === 'equalTo:') {
            $b_unwrap = isset($args[0]) ? $this->unwrap($args[0]) : null;

            if (!($b_unwrap instanceof IntegerValue)) {
                throw new \IPP\Student\Exception\ValueErrorException("Expected Integer argument");   
            }

            $b = $b_unwrap->getValue();
            return BooleanValue::from($a === $b);
        }

        if ($selector === 'lessThan:') {
            $b_unwrap = isset($args[0]) ? $this->unwrap($args[0]) : null;

            if (!($b_unwrap instanceof IntegerValue)) {
                throw new \IPP\Student\Exception\ValueErrorException("Expected Integer argument");   
            }

            $b = $b_unwrap->getValue();
            return BooleanValue::from($a < $b);
        }

        if ($selector === 'greaterThan:') {
            $b_unwrap = isset($args[0]) ? $this->unwrap($args[0]) : null;

        if (!($b_unwrap instanceof IntegerValue)) {
            throw new \IPP\Student\Exception\ValueErrorException("Expected Integer argument");   
        }

        $b = $b_unwrap->getValue();
            return BooleanValue::from($a > $b);
        }

        if ($selector === 'isNumber') {
            return BooleanValue::from(true);
        }

        $result = $this->handleObjectMethods($receiver, $selector, $args);
        if ($result !== null) {
            return $result;
        }

        throw new \IPP\Student\Exception\DoesNotUnderstandException("Unknown Integer method '$selector'");
    }

    /**
     * Execute block N times with optional param.
     * @param int $count
     * @param Value|null $block
     * @return Value
     * @throws IPPException
     */
    private function runTimesRepeat(int $count, ?Value $block): Value
    {
        if ($count < 0) {
            throw new \IPP\Student\Exception\ValueErrorException("timesRepeat: must be called on a non-negative integer");
        }

        if (!$block instanceof BlockInstance) {
            throw new \IPP\Student\Exception\ValueErrorException("Argument to timesRepeat: must be a block");
        }

        if ($this->blockRunner === null) {
            throw new \IPP\Core\Exception\InternalErrorException("BlockRunner is not set.");
        }

        $parameters = $block->getParameters();
        $paramName = $parameters[0]['name'] ?? null;

        try {
            for ($i = 0; $i < $count; $i++) {
                $memory = new Memory($block->getClosure());
                if ($paramName !== null) {
                    $memory->set($paramName, new IntegerValue($i+1));
                }
                $context = new ExecutionContext($block->getSelf(), $memory);
                $this->blockRunner->runBlock($block->getBody(), $context);
            }
        } catch (\IPP\Core\Exception\IPPException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error during timesRepeat: " . $e->getMessage(), $e
            );
        }

        return NilValue::get();
    }

    /**
     * Handle String built-in methods.
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value
     */
    private function handleString(Value $receiver, string $selector, array $args): Value
    {
        if (!($receiver instanceof StringValue)) {
            throw new \IPP\Student\Exception\ValueErrorException("Expected String receiver");
        }
        $text = $receiver->getValue();

        if($selector === 'read') {
            return new StringValue($this->input->readString() ?? '');
        }

        if ($selector === 'print') {
            $this->stdout->writeString($text);
            return $receiver;
        }

        if ($selector === 'asString') {
            return $receiver;
        }

        if ($selector === 'isString') {
            return BooleanValue::from(true);
        }

        if ($selector === 'asInteger') {
            if (is_numeric($text) && ctype_digit($text)) {
                return new IntegerValue((int)$text);
            } else {
                return NilValue::get();
            }
        }

        $arg = isset($args[0]) ? $this->unwrap($args[0]) : null;

        if ($selector === 'equalTo:') {
            if (isset($args[0]) && $arg instanceof StringValue) {
                return BooleanValue::from($text === $arg->getValue());
            } else {
                return BooleanValue::from(false);
            }
        }

        if ($selector === 'concatenateWith:') {
            if (isset($args[0]) && $arg instanceof StringValue) {
                return new StringValue($text . $arg->getValue());
            } else {
                return NilValue::get();
            }
        }

        if ($selector === 'startsWith:endsBefore:') {
            return $this->substring($text, $args);
        }


        $result = $this->handleObjectMethods($receiver, $selector, $args);
        if ($result !== null) {
            return $result;
        }

        throw new DoesNotUnderstandException("Unknown String method '$selector'");
    }

    /**
     * Return substring from start to end 
     * @param string $text
     * @param array<int, Value> $args
     * @return Value
     */
    private function substring(string $text, array $args): Value
    {
        if (
            !isset($args[0], $args[1]) ||
            !($args[0] instanceof IntegerValue) ||
            !($args[1] instanceof IntegerValue)
        ) {
            return NilValue::get();
        }

        $start = $args[0]->getValue();
        $end = $args[1]->getValue();

        if ($start <= 0 || $end <= 0 || $start >= $end) {
            return new StringValue("");
        }

        $startIndex = $start - 1;
        $length = $end - $start;

        if ($startIndex >= strlen($text)) {
            return new StringValue("");
        }

        return new StringValue(substr($text, $startIndex, $length));
    }

    /**
     * Handle Boolean built-in methods.
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value
     */
    private function handleBoolean(Value $receiver, string $selector, array $args): Value
    {
        $isTrue = $receiver->getClassName() === 'True';

        if ($selector === 'not') {
            return BooleanValue::from(!$isTrue);
        }

        $arg = isset($args[0]) ? $this->unwrap($args[0]) : null;

        if ($selector === 'and:') {
            if ($isTrue) {
                return $this->evaluateBooleanArg($arg);
            } else {
                return BooleanValue::from(false);
            }
        }

        if ($selector === 'or:') {
            if ($isTrue) {
                return BooleanValue::from(true);
            } else {
                return $this->evaluateBooleanArg($arg);
            }
        }

        if ($selector === 'ifTrue:ifFalse:') {
            return $this->handleIfTrueIfFalse($isTrue, $args);
        }

        if ($selector === 'equalTo:' || $selector === 'identicalTo:') { // not correct
            if (!($receiver instanceof BooleanValue)) {
                return BooleanValue::from(false);
            }
            if (!($arg instanceof BooleanValue)) {
                return BooleanValue::from(false);
            }
            return BooleanValue::from($receiver->getValue() === $arg->getValue());
        }

        $result = $this->handleObjectMethods($receiver, $selector, $args);
        if ($result !== null) {
            return $result;
        }

        throw new \IPP\Student\Exception\DoesNotUnderstandException("Unknown Boolean method '$selector'");
    }

    /**
     * Execute correct block from ifTrue:ifFalse:
     * @param bool $isTrue
     * @param array<int, Value> $args
     * @return Value
     */
    private function handleIfTrueIfFalse(bool $isTrue, array $args): Value
    {
        $trueBlock = $args[0] ?? null;
        $falseBlock = $args[1] ?? null;

        if ($isTrue && $trueBlock instanceof BlockInstance) {
            return $this->executeBlock($trueBlock);
        }

        if (!$isTrue && $falseBlock instanceof BlockInstance) {
            return $this->executeBlock($falseBlock);
        }

        return NilValue::get();
    }

    /**
     * Evaluate block for and:/or:
     *
     * @param Value|null $arg
     * @return Value
     */
    private function evaluateBooleanArg(?Value $arg): Value
    {
        if (!($arg instanceof BlockInstance)) {
            throw new \IPP\Student\Exception\TypeErrorException("Boolean shortcut requires block as argument");
        }

        return $this->executeBlock($arg);
    }

    /**
     * Handle Block built-in methods.
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value
     */
    private function handleBlock(Value $receiver, string $selector, array $args): Value
    {
        if (!($receiver instanceof BlockInstance)) {
            throw new \IPP\Student\Exception\ValueErrorException("Expected BlockInstance");
        }

        if ($selector === 'whileTrue:') {
            $condition = $receiver;
            $body = $args[0] ?? null;

            if (!($body instanceof BlockInstance)) {
                throw new \IPP\Student\Exception\ValueErrorException("Argument to whileTrue: must be a block");
            }

            while (true) {
                $result = $this->executeBlock($condition);
                if ($result->getClassName() !== 'True') break;

                $this->executeBlock($body);
            }

            return NilValue::get();
        }

        if ($selector === 'isBlock') {
            return BooleanValue::from(true);
        }

        if (str_starts_with($selector, 'value')) {
            $expectedArgs = substr_count($selector, ':');
            if (count($args) !== $expectedArgs) {
                throw new \IPP\Student\Exception\ArityException("Block expects $expectedArgs arguments, got " . count($args));
            }

            return $this->executeBlock($receiver, $args);
        }

        throw new \IPP\Student\Exception\DoesNotUnderstandException("Unknown Block method '$selector'");
    }

    /**
     * Run a block with arguments.
     *
     * @param BlockInstance $block
     * @param array<int, Value> $args
     * @return Value
     */
    private function executeBlock(BlockInstance $block, array $args = []): Value
    {
        if ($this->blockRunner === null) {
            throw new \IPP\Core\Exception\InternalErrorException("BlockRunner is not set.");
        }

        $params = $block->getParameters();
        $memory = new Memory($block->getClosure());

        foreach ($params as $index => $param) {
            $memory->set($param['name'], $args[$index] ?? NilValue::get());
        }

        $context = new ExecutionContext($block->getSelf(), $memory);

        try {
            return $this->blockRunner->runBlock($block->getBody(), $context);
        } catch (\IPP\Core\Exception\IPPException $e) {
            throw $e; 
        } catch (\Throwable $e) {
            throw new \IPP\Core\Exception\InternalErrorException(
                "Internal error while executing block: " . $e->getMessage(), $e
            );
        }
    }

    /**
     * Handle Nil built-in methods.
     *
     * @param Value $receiver
     * @param string $selector
     * @param array<int, Value> $args
     * @return Value
     */
    private function handleNil(Value $receiver, string $selector, array $args): Value
    {
        if (!($receiver instanceof NilValue)) {
            throw new \IPP\Student\Exception\ValueErrorException("Expected Nil receiver");
        }

        if ($selector === 'asString') {
            return new StringValue('nil');
        }

        if ($selector === 'isNil') {
            return BooleanValue::from(true);
        }

        $result = $this->handleObjectMethods($receiver, $selector, $args);
        if ($result !== null) {
            return $result;
        }

        throw new \IPP\Student\Exception\DoesNotUnderstandException("Unknown Nil method '$selector'");
    }

    /**
     * Unwrap 'value' attribute if present.
     * @param Value $value
     * @return Value
     */
    public static function unwrap(Value $value): Value
    {
        if ($value instanceof ObjectInstance && in_array('value', $value->getAttributeNames())) {
            return $value->getAttribute('value');
        }
        return $value;
    }

}


