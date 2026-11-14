# SOL25: Parser and Interpreter for a Smalltalk-like Language

A two-stage toolchain for **SOL25**, a small object-oriented language inspired by Smalltalk. It has classes, single inheritance, message passing, blocks (closures) and everything-is-an-object semantics.

1. **Parser** (Python): source code → lexical, syntactic and semantic analysis → XML representation of the AST
2. **Interpreter** (PHP 8, OOP): XML → runtime with classes, objects, dynamic dispatch and built-in types

> Project for *Principles of Programming Languages (IPP)*, Brno University of Technology, Faculty of Information Technology (2024/25).

## Example

```smalltalk
"Recursive factorial in SOL25"
class Main : Object {
    run [ |
        x := self factorial: 5.
        s := x asString.
        _ := s print.
    ]

    factorial: [ :n |
        r := (n equalTo: 0)
            ifTrue: [ | one := 1. ]
            ifFalse: [ | prev := self factorial: (n minus: 1). res := n multiplyBy: prev. ].
    ]
}
```

```bash
python3 parser/parse.py < parser/example.sol25 > program.xml
```

## Part 1: Parser (`parser/parse.py`)

A single-file Python 3 tool with four stages:

| Stage | Class | Responsibility | Exit code on error |
|---|---|---|---|
| Lexical analysis | `Lexer` | Regex-based tokenizer: identifiers, class names, keywords, integer and string literals with escapes, comments | 21 |
| Syntax analysis | `Parser` | Recursive-descent parser that builds the AST and a symbol table, with simple type inference | 22 |
| Semantic analysis | `SemanticAnalyzer` | Checks for `Main.run`, undefined variables, classes and class methods, block arity vs. selector, name collisions | 31–35 |
| Code generation | `XMLGenerator` | AST → XML (`ElementTree` + `minidom`) | — |

Details are in [`parser/DESIGN.md`](parser/DESIGN.md).

**Tests:** 125 of 142 community tests pass. The remaining failures are edge cases: some string escape sequences, whitespace inside keyword selectors, and circular-inheritance detection.

## Part 2: Interpreter (`interpreter/`)

An object-oriented PHP 8 interpreter that runs the XML program:

```text
Interpreter
 └─ ProgramRunner        loads classes from XML into the ClassRegistry, runs Main.run
     └─ BlockRunner      executes block statements in an ExecutionContext (self + Memory)
         └─ ExpressionEvaluator   literals, variables, blocks, message sends
             └─ MessageDispatcher  dynamic dispatch with inheritance
                 ├─ user methods (ClassDef / MethodDef)
                 ├─ BuiltinMethodHandler   Integer, String, Boolean, Nil, Block methods
                 └─ BuiltinConstructor     new / from: / read on classes (ClassReference)
```

- Runtime values implement a common `Value` interface (`IntegerValue`, `StringValue`, `BooleanValue`, `NilValue`, `ObjectInstance`, `BlockInstance`, `ClassReference`).
- Blocks are real closures: they capture `self` and their defining memory.
- Errors map to dedicated exception classes (`DoesNotUnderstandException`, `TypeErrorException`, `ValueErrorException`, …).

See [`interpreter/DESIGN.md`](interpreter/DESIGN.md) and the [UML class diagram](interpreter/uml_diagram.png).

### Running the interpreter

The interpreter plugs into the course's *ipp-core* framework (namespace `IPP\Core`: I/O, settings, error codes), which is not included here. The classes in `interpreter/` form the `IPP\Student` namespace of that framework. The framework requires PHP 8.4 with the `dom` extension.

```bash
php interpret.php --source=program.xml
```

## Tech stack

Python 3 (`re`, `xml.etree`, `minidom`), PHP 8.4 (OOP, PSR-4 autoload). The interpreter passes PHPStan at level 9 with no errors.
