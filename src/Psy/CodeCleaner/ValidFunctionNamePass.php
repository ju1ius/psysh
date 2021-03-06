<?php

/*
 * This file is part of Psy Shell
 *
 * (c) 2012-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Psy\CodeCleaner;

use PHPParser_Node as Node;
use PHPParser_Node_Expr as Expression;
use PHPParser_Node_Expr_FuncCall as FunctionCall;
use PHPParser_Node_Stmt_Function as FunctionStatement;
use Psy\Exception\FatalErrorException;

/**
 * Validate that function calls will succeed.
 *
 * This pass throws a FatalErrorException rather than letting PHP run
 * headfirst into a real fatal error and die.
 */
class ValidFunctionNamePass extends NamespaceAwarePass
{
    /**
     * Validate that function calls will succeed.
     *
     * @throws FatalErrorException if a function is redefined.
     * @throws FatalErrorException if the function name is a string (not an expression) and is not defined.
     *
     * @param Node $node
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof FunctionStatement) {
            $name = $this->getFullyQualifiedName($node->name);

            if (function_exists($name) || isset($this->currentScope[strtolower($name)])) {
                throw new FatalErrorException(sprintf('Cannot redeclare %s()', $name), 0, 1, null, $node->getLine());
            }

            $this->currentScope[strtolower($name)] = true;
        } elseif ($node instanceof FunctionCall) {
            // if function name is an expression, give it a pass for now.
            $name = $node->name;
            if (!$name instanceof Expression) {
                $shortName = implode('\\', $name->parts);
                $fullName  = $this->getFullyQualifiedName($name);

                if (
                    !isset($this->currentScope[strtolower($fullName)]) &&
                    !function_exists($shortName) &&
                    !function_exists($fullName)
                ) {
                    $message = sprintf('Call to undefined function %s()', $name);
                    throw new FatalErrorException($message, 0, 1, null, $node->getLine());
                }
            }
        }
    }
}
