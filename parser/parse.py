#Author: Daria Kinash xkinas00

import sys
import json
import xml.etree.ElementTree as ET
import xml.dom.minidom as minidom
import re

class Lexer:
    """
    A lexical analyzer that processes source code and extracts tokens.

    Attributes:
        source_code (str): The input source code.
        tokens (list): A list of extracted tokens(type, value).
        comments (list): A list of extracted comments.
    """

    def __init__(self, source_code):
        """
        Initializes the lexer.

        Args:
            source_code: The input source code.
        """
        self.source_code = source_code
        self.tokens = []
        self.comments = []

    def tokenize(self):
        """
        Splits the source code into lines and processes each line.
        """
        lines = self.source_code.split("\n") 
        
        for line in lines:
            self.process_line(line)

    def process_line(self, line):
        """
        Processes a single line of code and identifies tokens.

        Args:
            line: The line of code to be analyzed.
        """
        token_patterns = {
            "COMMENT": r'\".*?\"',  
            "CLASS_ID": r'[A-Z][a-zA-Z0-9]*', 
            "KEYWORD": r'\b(class|self|super|nil|true|false)\b', 
            "IDENTIFIER": r'[_a-z][_a-zA-Z0-9]*',   
            "NUMBER": r'[+-]?\d+',  
            "STRING": r"'([^'\\]|\\.)*'",  
            "OPERATOR": r':=|\.|:', 
            "SYMBOL": r'[\[\]{}|()]',  
        }

        position = 0
        while position < len(line):
            if line[position].isspace():
                position += 1
                continue

            for token_type, pattern in token_patterns.items():
                match = re.match(pattern, line[position:])
                if match:
                    token_value = match.group(0)
                    
                    if token_type == "COMMENT":
                        self.comments.append(token_value[1:-1]) 
                    
                    else:
                        self.tokens.append((token_type, token_value))
                    
                    position += len(token_value)
                    break
            else:
                self.error(21, "Lexical error")

    def error(self, code, message):
        """
        Prints an error message and exits the program.

        Args:
            code (int): The error code.
            message (str): A description of the error.
        """
        print(f"Error {code}: {message}", file=sys.stderr)
        sys.exit(code)


class Parser:
    """
    A parser that processes a tokenized source code and constructs an abstract syntax tree (AST).

    Attributes:
        tokens (list): A list of tokens extracted by the lexer.
        comments (list): A list of extracted comments.
        index (int): The current position in the token list.
        current_class: The name of the class currently being parsed.
        symbol_table (dict): Stores information about variables, methods, attributes, and classes.
    """
    def __init__(self, tokens, comments):
        """
        Initializes the parser with tokenized input and comments.

        Args:
            tokens (list): The list of tokens.
            comments (list): The list of comments.
        """
        self.tokens = tokens 
        self.comments = comments 
        self.index = 0  
        self.current_class = None
        self.symbol_table = {
            "variables": {},  # Stores declared variables with types
            "methods": {},    # Stores method names, params, return type
            "attributes": {}, # Stores instance attributes per class
            "classes": {}     # Stores class definitions, parent
        }
        self.add_builtin_classes()

    def add_builtin_classes(self):
        """
        Adds built-in classes and their methods to the symbol table.
        """
        built_in_classes = {
            "Object" : None,
            "Integer": "Object",
            "String": "Object",
            "True": "Object",
            "False": "Object",
            "Block": "Object",
            "Nil": "Object"
        }

        built_in_methods = {
            "Integer": {
                "equalTo:": ("Boolean", 1),
                "greaterThan:": ("Boolean", 1),
                "plus:": ("Integer", 1),
                "minus:": ("Integer", 1),
                "multiplyBy:": ("Integer", 1),
                "divBy:": ("Integer", 1),
                "asString": ("String", 0),
                "asInteger": ("Integer", 0)
            },
            "String": {
                "concatenateWith:": ("String", 1),
                "read": ("String", 0),
                "print": ("Nil", 0),
                "equalTo:": ("Boolean", 1),
                "asString": ("String", 0),
                "asInteger": ("Integer", 0),
                "startsWith:endsBefore:": ("String", 2)
            },
            "True": {
                "not": ("Boolean", 0),
                "and:": ("Boolean", 1),
                "or:": ("Boolean", 1),
                "ifTrue:ifFalse:": ("Unknown", 2)
            },
            "False": {
                "not": ("Boolean", 0),
                "and:": ("Boolean", 1),
                "or:": ("Boolean", 1),
                "ifTrue:ifFalse:": ("Unknown", 2) ######
            },
            "Block": {
                "whileTrue:": ("Unknown", 1)
            },
            "Nil": {
                "asString": ("String", 0)
            },
            "Object": {
                "identicalTo:": ("Boolean", 1),
                "equalTo:": ("Boolean", 1),
                "asString": ("String", 0),
                "isString": ("Boolean", 0),
                "isNumber": ("Boolean", 0),
                "isBlock": ("Boolean", 0),
                "isNil": ("Boolean", 0)
            }
        }

        for class_name, parent in built_in_classes.items():
            self.symbol_table["classes"][class_name] = {"parent": parent}

        for class_name, methods in built_in_methods.items():
            for method_name, (return_type, arity) in methods.items():
                self.symbol_table["methods"][method_name] = {
                    "class": class_name,
                    "parameters": ["arg"] * arity,  
                    "return_type": return_type
                }

    def current_token(self):
        """
        Returns the current token or None if the end of the token list is reached.
        """
        return self.tokens[self.index] if self.index < len(self.tokens) else None

    def next_token(self):
        """
        Returns the next token without advancing the index.
        """
        if self.index + 1 < len(self.tokens):
            return self.tokens[self.index + 1]
        return None

    def match(self, expected_type, expected_value=None):
        """
        Matches the current token against the expected type and optionally the expected value.

        Args:
            expected_type (str): The expected token type.
            expected_value (str, optional): The expected token value.

        Returns:
            tuple: The matched token.
            SyntaxError: If the token does not match.
        """

        if self.current_token() and self.current_token()[0] == expected_type:
            if expected_value is None or self.current_token()[1] == expected_value:
                token = self.tokens[self.index]
                self.index += 1
                return token  
        self.error(22, f"Expected {expected_value or expected_type}, but found {self.current_token()}")

    def parse_program(self):
        """
        Parses the entire program.

        Returns:
            dict: The abstract syntax tree (AST) representation of the program.
        """
        program_node = {"type": "Program", "classes": [], "comments": self.comments}

        if self.current_token() and self.current_token()[0] != "KEYWORD" and self.current_token()[1] != "class":
            self.error(22, f"Expected keyword class")

        while self.current_token() and self.current_token()[0] == "KEYWORD" and self.current_token()[1] == "class":
            class_node = self.parse_class()
            program_node["classes"].append(class_node)
            self.symbol_table["classes"][class_node["name"]] = {"parent": class_node["parent"]}

        return program_node 

    def parse_class(self):
        """
        Parses a class declaration.

        Returns:
            dict: The parsed class node.
        """
        self.match("KEYWORD", "class")
        class_name = self.match("CLASS_ID")[1]

        if class_name in self.symbol_table["classes"]:
            self.error(35, f"Class '{class_name}' is already defined.")

        self.match("OPERATOR", ":")
        parent_name = self.match("CLASS_ID")[1]
        self.match("SYMBOL", "{")

        self.current_class = class_name
        self.symbol_table["attributes"][class_name] = {}

        methods = []
        if self.current_token() and self.current_token()[0] not in ["IDENTIFIER", "SYMBOL"]:
            self.error(22, "Expected method name or '}' after class declaration")

        while self.current_token() and self.current_token()[0] == "IDENTIFIER":
            method_node = self.parse_method()
            methods.append(method_node)
            
            selector_name = "".join(method_node["selector"]["parts"])
            self.symbol_table["methods"][selector_name] = {
                "class": class_name,
                "parameters": method_node["block"]["parameters"],
                "return_type": self.infer_return_type(method_node["block"])
            }

        self.match("SYMBOL", "}")
        class_node = {"type": "Class", "name": class_name, "parent": parent_name, "methods": methods}
        return class_node


    def parse_method(self):
        """
        Parses a method definition.

        Returns:
            dict: The parsed method node.
        """
        selector = self.parse_selector()
        block = self.parse_block()
        return {"type": "Method", "selector": selector, "block": block}
    
    def parse_selector(self):
        """
        Parses a method selector.

        Returns:
            dict: The parsed selector node.
        """
        selector_parts = []

        if not self.current_token() or self.current_token()[0] != "IDENTIFIER":
            self.error(22, "Expected an id at the start of a selector")

        while self.current_token() and self.current_token()[0] == "IDENTIFIER":
            selector_part = self.match("IDENTIFIER")[1]
            
            if self.current_token() and self.current_token()[0] == "OPERATOR" and self.current_token()[1] == ":":
                self.match("OPERATOR", ":") 
                selector_part += ":"  
            
            selector_parts.append(selector_part)

        return {"type": "Selector", "parts": selector_parts}


    def parse_block(self):
        """
        Parses a method block.

        Returns:
            dict: The parsed block node with parameters and statements.
        """
        self.match("SYMBOL", "[")  

        parameters = []
        if self.current_token() and self.current_token()[0] == "SYMBOL" and self.current_token()[1] == "|":
            self.match("SYMBOL", "|") 

        elif (
                self.current_token() and self.current_token()[0] == "OPERATOR" and self.current_token()[1] == ":"
                and self.next_token() and self.next_token()[0] == "IDENTIFIER"
            ):
                parameters = self.parse_block_par()
                self.match("SYMBOL", "|")
        else:
            self.error(22, f"Missing symbol '|'")
            
        statements = self.parse_block_stat()

        self.match("SYMBOL", "]") 
        return {
            "type": "Block",
            "parameters": parameters,
            "statements": statements
        }
    
    def parse_block_par(self):
        """
        Parses the parameters of a block.

        Returns:
            list: A list of parameter names.
        """
        parameters = []
        while (
            self.current_token() and self.current_token()[0] == "OPERATOR" and self.current_token()[1] == ":"
            and self.next_token() and self.next_token()[0] == "IDENTIFIER"
        ):
            self.match("OPERATOR", ":")  
            param_name = self.match("IDENTIFIER")[1] 
            parameters.append(param_name)  
            self.symbol_table["variables"][param_name] = {"type": "Unknown"}

        return parameters  

    def parse_block_stat(self):
        """
        Parses statements inside a block.

        Returns:
            list: A list of assignment statements.
        """
        statements = []
        if self.current_token() and self.current_token()[0] not in ["IDENTIFIER", "SYMBOL"]:
            self.error(22, "Expected an identifier or symbol ']' ")

        while self.current_token() and self.current_token()[0] == "IDENTIFIER":
            var_name = self.match("IDENTIFIER")[1]
            self.match("OPERATOR", ":=")
            expr = self.parse_expr()
            self.match("OPERATOR", ".")

            expr_type = self.infer_expr_type(expr) 
            self.symbol_table["variables"][var_name] = {"type": expr_type}

            statements.append({"type": "Assign", "var": var_name, "expr": expr})
        return statements

    def parse_expr(self):
        """
        Parses an expression.

        Returns:
            dict: Parsed expression structure.
        """
        expr_base = self.parse_expr_base()
        expr_tail = self.parse_expr_tail()

        if expr_base["type"] == "Self" and expr_tail:
            return {"type": "SelfCall", "base": expr_base, "tail": expr_tail}

        return {"type": "Expr", "base": expr_base, "tail": expr_tail}

    def parse_expr_base(self):
        """
        Parses the base of an expression.

        Returns:
            dict: The base node of the expression.
        """
        if self.current_token():
            token_type, token_value = self.current_token()

            # Handle different expression base types.
            if token_type == "NUMBER":
                return {"type": "Literal", "value": self.match("NUMBER")[1], "class": "Integer"}

            elif token_type == "STRING":
                return {"type": "Literal", "value": self.match("STRING")[1], "class": "String"}

            elif token_type == "IDENTIFIER":
                return {"type": "Variable", "name": self.match("IDENTIFIER")[1]}

            elif token_type == "CLASS_ID":
                return {"type": "ClassIdentifier", "name": self.match("CLASS_ID")[1]}
            
            elif token_type == "KEYWORD":
                if token_value == "true":
                    return {"type": "Literal", "value": self.match("KEYWORD")[1], "class": "True"}
                elif token_value == "false":
                    return {"type": "Literal", "value": self.match("KEYWORD")[1], "class": "False"}
                elif token_value == "nil":
                    return {"type": "Literal", "value": self.match("KEYWORD")[1], "class" : "Nil"} 
                elif token_value == "self":
                    self.match("KEYWORD", "self")
                    return {"type": "Self"}  
                elif token_value == "super":
                    self.match("KEYWORD", "super")
                    return {"type": "Super"}  

            elif token_type == "SYMBOL" and token_value == "[":
                return self.parse_block()

            elif token_type == "SYMBOL" and token_value == "(":
                self.match("SYMBOL", "(") 
                expr = self.parse_expr()
                self.match("SYMBOL", ")")  
                return expr
            
        self.error(22, f"Expected expression base")

    def parse_expr_tail(self):
        """
        Parses the tail of an expression, handling method calls and attribute access.

        Returns:
            list: A list of method selectors and arguments.
        """
        expr_tail = []
        if (
            self.current_token() and self.current_token()[0] == "IDENTIFIER"
            and self.next_token() and self.next_token()[0] == "OPERATOR"
            and self.next_token()[1] == ":"
        ):
            expr_tail.extend(self.parse_expr_sel())  
        elif self.current_token() and self.current_token()[0] == "IDENTIFIER":
            expr_tail.append({"selector": self.match("IDENTIFIER")[1], "arg": []})

        return expr_tail

    def parse_expr_sel(self):
        """
        Parses a method call with arguments.

        Returns:
            list: List of parsed method calls.
        """
        expr_sel = []
        
        selector_parts = [] 
        arguments = []  
        
        while (
            self.current_token() and self.current_token()[0] == "IDENTIFIER"
            and self.next_token() and self.next_token()[0] == "OPERATOR" and self.next_token()[1] == ":"
        ):
            part = self.match("IDENTIFIER")[1]  
            self.match("OPERATOR", ":")  
            
            selector_parts.append(part + ":")  
            arguments.append(self.parse_expr_base()) 
        
        if selector_parts: 
            expr_sel.append({
                "selector": "".join(selector_parts),  
                "arg": arguments 
            })
        
        return expr_sel
    
    def infer_expr_type(self, expr: dict):
        """
        Infers the type of a given expression.

        Args:
            expr (dict): The parsed expression node.

        Returns:
            str: The inferred type of the expression, or "Unknown" if it cannot be determined.
        """
        if not isinstance(expr, dict):
            return "Unknown"

        if expr.get("type") == "Literal":
            return expr.get("class", "Unknown")

        if expr.get("type") == "Variable":
            var_name = expr.get("name")
            return self.symbol_table["variables"].get(var_name, {}).get("type", "Unknown")


        if expr["type"] == "Super":
            return self.symbol_table["classes"][self.current_class]["parent"]

        if expr.get("type") == "ClassIdentifier":
            return expr.get("name")

        if expr.get("type") == "SelfCall":
            base_type = self.current_class  
            tail = expr.get("tail", [])

            for selector in tail:
                method_name = selector["selector"]
                args = selector.get("arg", [])

                if len(args) == 1:
                    attr_name = method_name.rstrip(":") 
                    attr_value_type = self.infer_expr_type(args[0]) 

                    if base_type not in self.symbol_table["attributes"]:
                        self.symbol_table["attributes"][base_type] = {}

                    self.symbol_table["attributes"][base_type][attr_name] = attr_value_type
                    return attr_value_type  

                if len(args) == 0 and method_name in self.symbol_table["attributes"].get(base_type, {}):
                    attr_type = self.symbol_table["attributes"][base_type][method_name]
                    return attr_type

                for arg in args:
                    self.infer_expr_type(arg)

            return self.symbol_table["methods"].get(method_name, {}).get("return_type", "Unknown")

        if expr.get("type") == "Expr":
            base_expr = expr.get("base")
            base_type = self.infer_expr_type(base_expr)  
            tail = expr.get("tail", [])

            for selector in tail:
                method_name = selector["selector"]
                args = selector.get("arg", [])

                for arg in args:
                    self.infer_expr_type(arg)

                method_type = self.symbol_table["methods"].get(method_name, {}).get("return_type", "Unknown")
                return method_type

            return base_type

        return "Unknown"
    
    def infer_return_type(self, block: dict):
        """
        Infers the return type of a block based on its last statement.

        Args:
            block (dict): The parsed block node.

        Returns:
            str: The inferred return type of the block, or "Nil" if no valid return type is found.
        """
        if block["statements"]:
            last_statement = block["statements"][-1]
            if last_statement["type"] == "Assign":
                return self.infer_expr_type(last_statement["expr"])
        return "Nil"

    def error(self, code, message):
        """
        Prints an error message and exits the program.

        Args:
            code (int): The error code.
            message (str): A description of the error.
        """
        print(f"Syntax Error: {message}", file=sys.stderr)
        sys.exit(code)  



class SemanticAnalyzer:
    """
    Performs semantic analysis on the abstract syntax tree (AST).

    Attributes:
        ast (dict): The parsed source code structure.
        symbol_table (dict): Stores classes, methods, and variables.
        current_class: The class currently being analyzed.
        current_scope_stack (list): Tracks variable scopes within blocks.
    """
    def __init__(self, ast, symbol_table):
        """
        Initializes the semantic analyzer.

        Args:
            ast (dict): The abstract syntax tree (AST) of the program.
            symbol_table (dict): The symbol table containing class, method, and variable information.
        """
        self.ast = ast
        self.symbol_table = symbol_table
        self.current_class = None
        self.current_scope_stack = [{}]   

    def analyze(self):
        """
        Runs all semantic checks in the correct order.
        """
        self.validate_main_class()
        self.validate_classes()
        self.validate_methods()
        self.validate_blocks()

    def validate_main_class(self):
        """
        Ensures that the program contains a 'Main' class with a 'run' method.
        """
        if "Main" not in self.symbol_table["classes"]:
            self.error(31, "Missing class 'Main'.")
        if not self.get_method_info("Main", "run"):
            self.error(31, "Missing method 'run' in class 'Main'.")

    def validate_classes(self):
        """
        Checks that all classes have valid parent classes.
        """
        for class_node in self.ast["classes"]:
            class_name = class_node["name"]
            parent_name = class_node["parent"]
            if parent_name not in self.symbol_table["classes"] and parent_name != "Object":
                self.error(32, f"Undefined parent class '{parent_name}' for class '{class_name}'.")

    def validate_methods(self):
        """
        Validates methods, ensuring they are properly defined and have correct arity.
        """
        for class_node in self.ast["classes"]:
            self.current_class = class_node["name"]

            for method in class_node["methods"]:
                selector_name = "".join(method["selector"]["parts"])
                expected_arity = len(method["block"]["parameters"])

                method_info = self.get_method_info(self.current_class, selector_name)
                if not method_info:
                    self.error(32, f"Method '{selector_name}' in class '{self.current_class}' is undefined.")

                if len(method_info["parameters"]) != expected_arity:
                    self.error(33, f"Method '{selector_name}' in class '{self.current_class}' expects {len(method_info['parameters'])} parameters, but block has {expected_arity}.")

    def validate_blocks(self):
        """
        Validates blocks inside methods to ensure correct variable usage and assignments.
        """
        for class_node in self.ast["classes"]:
            for method in class_node["methods"]:
                expected_arity = sum(1 for part in method["selector"]["parts"] if part.endswith(":"))
                self.validate_block(method["block"], expected_arity)

    def validate_block(self, block_node, expected_arity=None):
        """
        Validates a method block, ensuring correct arity and checking variable usage.

        Args:
            block_node (dict): The parsed block node containing parameters and statements.
            expected_arity (int, optional): The expected number of parameters for the block.
        """
        self.current_scope_stack.append({})
        parameters = block_node["parameters"]

        if expected_arity is not None and len(parameters) != expected_arity:
            self.error(33, f"Block arity mismatch. Expected {expected_arity}, but found {len(parameters)}.")

        parameter_set = set()
        for param in parameters:
            if param in parameter_set:
                self.error(34, f"Duplicate parameter '{param}' in block.")
            parameter_set.add(param)
            self.current_scope_stack[-1][param] = "Unknown"

        for statement in block_node["statements"]:
            if statement["type"] == "Assign":
                var_name = statement["var"]
                if var_name in parameter_set:
                    self.error(34, f"Variable '{var_name}' conflicts with a block parameter.")
                    
                self.validate_assignment(statement)

        self.current_scope_stack.pop()

    def validate_assignment(self, assign_node):
        """
        Validates an assignment statement, ensuring the variable is properly typed.

        Args:
            assign_node (dict): The parsed assignment statement node.
        """
        var_name = assign_node["var"]
        expr_type = self.get_expr_type(assign_node["expr"])

        if var_name not in self.current_scope_stack[-1]:
            self.current_scope_stack[-1][var_name] = expr_type
        self.symbol_table["variables"][var_name]["type"] = expr_type
        self.validate_expr(assign_node["expr"])

    def validate_expr(self, expr_node):
        """
        Validates an expression, ensuring variables and method calls are valid.

        Args:
            expr_node (dict): The parsed expression node.
        """
        if expr_node["type"] == "Literal":
            return

        if expr_node["type"] == "Variable":
            var_name = expr_node["name"]
            found = any(var_name in scope for scope in reversed(self.current_scope_stack))
            if not found:
                self.error(32, f"Variable '{var_name}' used before assignment.")

        if expr_node["type"] == "ClassIdentifier":
            class_name = expr_node["name"]
            if class_name not in self.symbol_table["classes"]:
                self.error(32, f"Undefined class '{class_name}' used.")

        if expr_node["type"] == "Self":
            return

        if expr_node["type"] == "SelfCall":
            self.validate_self_call(expr_node)

        if expr_node["type"] == "Expr":
            self.validate_expr(expr_node["base"])
            if "tail" in expr_node and isinstance(expr_node["tail"], list):
                for selector in expr_node["tail"]:
                    self.validate_selector(selector, expr_node["base"])

    def validate_self_call(self, expr_node):
        """
        Validates a self-call expression, ensuring the method or attribute exists in the current class.

        Args:
            expr_node (dict): The parsed expression node representing a self-call.
        """
        if "tail" in expr_node and isinstance(expr_node["tail"], list):
            for selector in expr_node["tail"]:
                method_name = selector["selector"]

                if method_name.endswith(":"):
                    attr_name = method_name[:-1]  
        
                    if attr_name not in self.symbol_table["attributes"][self.current_class]:
                        method_info = self.get_method_info(self.current_class, method_name)
                        if not method_info:
                            self.error(32, f"Undefined attribute or method '{attr_name}' in class '{self.current_class}'.")
                        else:
                            self.validate_selector(selector, expr_node)  
                else:
                    if method_name not in self.symbol_table["attributes"][self.current_class]:
                        method_info = self.get_method_info(self.current_class, method_name)
                        if not method_info:
                            self.error(32, f"Undefined attribute or method '{method_name}' in class '{self.current_class}'.")
                        else:
                            self.validate_selector(selector, expr_node)

    def validate_selector(self, selector, base_expr):
        """
        Validates a method call selector, ensuring the method exists and has the correct arity.

        Args:
            selector (dict): The parsed selector node representing a method call.
            base_expr (dict): The base expression upon which the method is called.
        """
        method_name = selector["selector"]
        args = selector["arg"] if isinstance(selector["arg"], list) else [selector["arg"]]
        arg_count = len(args)

        if method_name == "new" and arg_count == 0:
            return 
        
        if method_name == "from:" and arg_count == 1:
            for arg in args:
                self.validate_expr(arg)
            return 

        control_methods = {"ifTrue:ifFalse:", "whileTrue:", "new", "from:"}
        if method_name in control_methods:
            return 

        base_type = self.get_expr_type(base_expr)
        method_info = self.get_method_info(self.current_class, method_name)

        if not method_info:
            base_type = self.get_expr_type(base_expr)
            method_info = self.get_method_info(base_type, method_name)

        if not method_info:
            # Class methods are known statically, so an unknown one is an error.
            # Messages to objects are resolved at runtime (doesNotUnderstand),
            # so for them only the arguments are checked.
            if base_expr.get("type") == "ClassIdentifier":
                self.error(32, f"Undefined method '{method_name}' in class '{self.current_class}' or '{base_type}'.")
            for arg in args:
                self.validate_expr(arg)
            return

        expected_arity = len(method_info["parameters"])
        if arg_count != expected_arity:
            self.error(33, f"Method '{method_name}' in class '{base_type}' expects {expected_arity} arguments, but got {arg_count}.")

        for arg in args:
            self.validate_expr(arg)


    def get_expr_type(self, expr_node):
        """
        Retrieves the type information of an expression from the symbol table.

        Args:
            expr_node (dict): The parsed expression node.

        Returns:
            str: The inferred type of the expression, or "Unknown" if the type cannot be determined.
        """

        if expr_node["type"] == "Literal":
            return expr_node.get("class", "Unknown")

        if expr_node["type"] == "Variable":
              return self.symbol_table["variables"].get(expr_node["name"], {}).get("type", "Unknown")

        if expr_node["type"] == "Self":
            return self.current_class
        
        if expr_node["type"] == "Super":
            parent_class = self.symtable["classes"][self.current_class]["parent"]
            if parent_class is None:
                self.error(32, f"Class '{self.current_class}' has no parent but used 'super'.")
            return parent_class

        if expr_node["type"] == "SelfCall":
            base_type = self.current_class 
            tail = expr_node.get("tail", [])

            for selector in tail:
                member_name = selector["selector"]
                attr_name = member_name.rstrip(":")
                args = selector.get("arg", [])

                if member_name in self.symbol_table["methods"]:
                    method_info = self.symbol_table["methods"][member_name]
                    if method_info["class"] == base_type:
                        return method_info["return_type"]
                    
                if attr_name in self.symbol_table["attributes"].get(base_type, {}):
                    return self.symbol_table["attributes"][base_type][attr_name]

                return "Unknown"

            return base_type  

        if expr_node["type"] == "Expr":
            base_type = self.get_expr_type(expr_node["base"])
            tail = expr_node.get("tail", [])

            for selector in tail:
                member_name = selector["selector"]
                args = selector.get("arg", [])

                if member_name in self.symbol_table["methods"]:
                    method_info = self.symbol_table["methods"][member_name]
                    if method_info["class"] == base_type:
                        return method_info["return_type"]

                return "Unknown"  

            return base_type

        return "Unknown"


    def get_method_info(self, class_name, method_name):
        """
        Searches for a method in a class and its parent classes.

        Args:
            class_name (str): The name of the class.
            method_name (str): The name of the method to search for.

        Returns:
            dict or None: The method information if found, otherwise None.
        """
        if class_name not in self.symbol_table["classes"]:
            return None  
        if method_name in self.symbol_table["methods"]:
            method_info = self.symbol_table["methods"][method_name]
            if method_info["class"] == class_name:
                return method_info

        parent_class = self.symbol_table["classes"].get(class_name, {}).get("parent")
        if parent_class:
            return self.get_method_info(parent_class, method_name)

        return None  

    def error(self, code, message):
        """
        Prints an error message and exits the program.

        Args:
            code (int): The error code.
            message (str): A description of the error.
        """
        print(f"Semantic Error {code}: {message}", file=sys.stderr)
        sys.exit(code)  


class XMLGenerator:
    """
    Generates an XML representation of the abstract syntax tree (AST) 

    Attributes:
        ast (dict): The parsed abstract syntax tree structure.
    """
    def __init__(self, ast):
        """
        Initializes the XML generator.

        Args:
            ast (dict): The abstract syntax tree representing the source code.
        """
        self.ast = ast  

    def generate_xml(self):
        """
        Generates the XML string representation of the program.

        Returns:
            str: The formatted XML string.
        """
        root = self.xml()  
        return self.xml_str(root)

    def xml(self):
        """
        Creates the root <program> XML element and processes classes.

        Returns:
            ET.Element: The root XML element.
        """
        program = ET.Element("program")
        program.set("language", "SOL25")

        
        if self.ast["comments"]:
            first_comment = self.ast["comments"][0]
            program.set("description", first_comment)

        for class_node in self.ast["classes"]:
            program.append(self.process_class(class_node))

        return program

    def xml_str(self, root: ET.Element):
        """
        Converts the XML tree into a formatted string.

        Args:
            root (ET.Element): The root XML element.

        Returns:
            str: The formatted XML string.
        """
        xml_string = ET.tostring(root, encoding="utf-8", method="xml", xml_declaration=True)
        return minidom.parseString(xml_string).toprettyxml(indent="    ", encoding="UTF-8").decode("utf-8")

    def process_class(self, class_node):
        """
        Creates an XML element for a class.

        Args:
            class_node (dict): The class node from the AST.

        Returns:
            ET.Element: The <class> XML element.
        """
        class_element = ET.Element("class")
        class_element.set("name", class_node["name"])
        class_element.set("parent", class_node["parent"])

        for method in class_node["methods"]:
            class_element.append(self.process_method(method))

        return class_element

    def process_method(self, method_node):
        """
        Creates an XML element for a method.

        Args:
            method_node (dict): The method node from the AST.

        Returns:
            ET.Element: The <method> XML element.
        """
        selector_name = "".join(method_node["selector"]["parts"])
        method_element = ET.Element("method")
        method_element.set("selector", selector_name)

        method_element.append(self.process_block(method_node["block"]))
        return method_element

    def process_block(self, block_node):
        """
        Creates an XML element for a block of statements.

        Args:
            block_node (dict): The block node from the AST.

        Returns:
            ET.Element: The <block> XML element.
        """
        block_element = ET.Element("block", arity=str(len(block_node["parameters"])))

        for i, param in enumerate(block_node["parameters"], start=1):
            ET.SubElement(block_element, "parameter", order=str(i), name=param)

        for i, statement in enumerate(block_node["statements"], start=1):
            if statement["type"] == "Assign":
                block_element.append(self.process_assignment(statement, i))  

        return block_element

    def process_assignment(self, assign_node, order):
        """
        Creates an XML element for an assignment statement.

        Args:
            assign_node (dict): The assignment node from the AST.
            order (int): The order of the assignment statement in the block.

        Returns:
            ET.Element: The <assign> XML element.
        """
        assign_element = ET.Element("assign", order=str(order))
        ET.SubElement(assign_element, "var", name=assign_node["var"])

        expr_element = ET.SubElement(assign_element, "expr")
        self.process_expr(assign_node["expr"], expr_element)

        return assign_element

    def process_expr(self, expr_node, parent_element):
        """
        Creates an XML element for an expression.

        Args:
            expr_node (dict): The expression node from the AST.
            parent_element (ET.Element): The parent XML element where the expression should be added.
        """
        if expr_node is None:
            return 
        
        if isinstance(expr_node, list):
            for expr in expr_node:  
                self.process_expr(expr, parent_element)  
            return  

        # Process different types of expressions
        if expr_node["type"] == "Literal":
            value = expr_node["value"]

            if value.lstrip("+-").isdigit():  
                class_name = "Integer"
            elif value.startswith("'") and value.endswith("'"):  
                class_name = "String"
                value = value[1:-1]  
            elif value == "true":  
                class_name = "True"
            elif value == "false":  
                class_name = "False"
            elif value == "nil":
                class_name = "Nil"
            else:
                class_name = "Unknown"

            ET.SubElement(parent_element, "literal",
                        **({"class": class_name} if class_name else {}),
                        value=value) 
        

        elif expr_node["type"] == "Variable":
            ET.SubElement(parent_element, "var", name=expr_node["name"])

        elif expr_node["type"] == "Self":
            ET.SubElement(parent_element, "var", name="self")

        elif expr_node["type"] == "Super":
            ET.SubElement(parent_element, "var", name="super")
        
        elif expr_node["type"] == "Keyword":
            attributes = {
                "class": expr_node["value"].capitalize(),
                "value": expr_node["value"]
            }
            
            ET.SubElement(parent_element, "literal", **attributes)

        elif expr_node["type"] == "ClassIdentifier":
            attributes = {
                "value": expr_node["name"],
                "class": "class"
            }
            ET.SubElement(parent_element, "literal", **attributes)

        elif expr_node["type"] == "SelfCall":

            for i, selector in enumerate(expr_node["tail"], start=1):
                method_name = selector["selector"]
                args = selector.get("arg", [])

                selector_element = ET.SubElement(parent_element, "send", selector=method_name)

                receiver_expr = ET.SubElement(selector_element, "expr")
                ET.SubElement(receiver_expr, "var", name="self")

                for j, arg in enumerate(args, start=1):
                    arg_element = ET.SubElement(selector_element, "arg", order=str(j))
                    expr_arg = ET.SubElement(arg_element, "expr")
                    self.process_expr(arg, expr_arg)

        elif expr_node["type"] == "Expr":
            if expr_node.get("tail"):
                first_selector = expr_node["tail"][0]

                full_selector = first_selector["selector"]
                args = [first_selector["arg"]]

                for selector in expr_node["tail"][1:]:
                    full_selector += ":" + selector["selector"]
                    args.append(selector["arg"])

                send_element = ET.SubElement(parent_element, "send", selector=full_selector)

                receiver_expr = ET.SubElement(send_element, "expr")
                self.process_expr(expr_node["base"], receiver_expr)

                for i, argument in enumerate(args, start=1):
                    if argument is None:  
                        continue
                    if isinstance(argument, list): 
                        for j, sub_arg in enumerate(argument, start=i):
                            arg_element = ET.SubElement(send_element, "arg", order=str(j))
                            expr_arg = ET.SubElement(arg_element, "expr")
                            self.process_expr(sub_arg, expr_arg)
                    else:
                        arg_element = ET.SubElement(send_element, "arg", order=str(i))
                        expr_arg = ET.SubElement(arg_element, "expr")
                        self.process_expr(argument, expr_arg)
            else:
                self.process_expr(expr_node["base"], parent_element)

        elif expr_node["type"] == "Block":
            block_element = ET.SubElement(parent_element, "block", arity=str(len(expr_node["parameters"])))
            for i, param in enumerate(expr_node["parameters"], start=1):
                ET.SubElement(block_element, "parameter", order=str(i), name=param)
            for i, statement in enumerate(expr_node["statements"], start=1):
                if statement["type"] == "Assign":
                    block_element.append(self.process_assignment(statement, i))


def parse_arguments():
    """
    Parses command-line arguments and handles help requests
    """
    args = sys.argv[1:] 

    if not args:
        return  

    if args == ["-h"] or args == ["--help"]:
        print("Usage: parse.py [options]\n\nOptions:\n  -h, --help")
        sys.exit(0)  

    print("Error: Invalid arguments provided.", file=sys.stderr)
    sys.exit(10)



def main():
    # Handle arguments
    parse_arguments()

    # Read input source code
    source_code = sys.stdin.read()

    # Lexical analysis - Tokenize the input
    lexer = Lexer(source_code)
    lexer.tokenize()

    # Parsing - Generate an abstract syntax tree (AST)
    parser = Parser(lexer.tokens, lexer.comments)
    ast = parser.parse_program()
    symbol_table = parser.symbol_table

    # Semantic analysis - Validate the AST and check for errors
    semantic_analyzer = SemanticAnalyzer(ast, symbol_table)
    semantic_analyzer.analyze()

    # XML Generation - Convert AST into XML format
    xml_generator = XMLGenerator(ast)
    xml_output = xml_generator.generate_xml()

    # Output XML
    print(xml_output)


if __name__ == "__main__":
    main()
