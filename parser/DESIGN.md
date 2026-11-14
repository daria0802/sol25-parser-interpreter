Documentation of Project Implementation for IPP 2024/2025
Name and surname: Daria Kinash
Login: xkinas00


## 1. Program Description
The `parse.py` script is a code analyzer for the SOL25 language, implemented in Python 3.11. The program performs lexical, syntactic, and semantic analysis of source code and generates its XML representation according to the specifications.

## 2. Input and Output
- **Input:** SOL25 source code from standard input.
- **Output:** XML representation of the Abstract Syntax Tree (AST) to standard output.
- **Error Messages:** Sent to standard error output with corresponding error codes.

## 3. Program Structure
The program is divided into the following main components:

### 3.1 Lexer
The `Lexer` class breaks the input source code into tokens. It processes each line using predefined regular expressions to identify different types of tokens. Tokens are stored in a list and later used by the parser. 
The lexer supports:

- **Comments:** (`" comment "`).
- **Class Identifiers:** Class names.
- **Keywords:** (`class`, `self`, `super`, `nil`, `true`, `false`).
- **Identifiers:** Variable and method names.
- **Numbers:** Integer literals.
- **Strings:** Single-quoted sequences with escape characters.
- **Operators:** Assignment (`:=`), method call (`.`), and selector (`:`).
- **Symbols:** Brackets, braces, pipes, and parentheses.

If an invalid token is encountered, the lexer reports a lexical error (code 21) and terminates execution.

### 3.2 Parser

The `Parser` class constructs an **Abstract Syntax Tree (AST)** from the tokenized input. It follows the **SOL25 grammar** to identify and organize:

- Class declarations and inheritance structures
- Method definitions with selectors and blocks
- Expressions, assignments, and message sends

The parser ensures correct nesting of blocks, validates syntactic correctness, and constructs a symbol table to track declared entities. The **symbol table** is implemented as a dictionary mapping identifiers to their properties. If the syntax is incorrect, an error (code 22) is raised.

### 3.3 Semantic Analyzer
The `SemanticAnalyzer` class checks for logical correctness of the parsed AST using the symbol table. It verifies:
- The existence of a `Main` class and a `run` method (error 31).
- That variables and methods are declared before use (error 32).
- That method calls use the correct number of parameters (error 33).
- That variable names do not conflict with block parameters (error 34).

### 3.4 XML Generator
The `XMLGenerator` class transforms the AST into an XML representation of the SOL25 program. Each element in the AST is converted into a corresponding XML node with attributes that describe its structure. The XML is formatted using `minidom` and printed to `stdout`.


## 6. Execution
The program can be run as follows:
```sh
python3.11 parse.py < input.sol
```
To display the help message:
```sh
python3.11 parse.py --help
```
