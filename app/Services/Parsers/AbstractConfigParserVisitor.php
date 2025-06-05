<?php

namespace App\Services\Parsers;

use PhpParser\Node;
// NodeVisitorAbstract is no longer extended directly by this class.
// It's just a helper base for the actual NodeProcessors.

/**
 * Abstract base class providing common scalar value resolution logic for PHP-Parser processing.
 */
abstract class AbstractConfigParserVisitor 
{
    /**
     * Resolves a Node\Expr to its scalar PHP value (string, int, float, bool, null).
     *
     * @param Node\Expr $expr The expression node to resolve.
     * @return string|int|float|bool|null The resolved scalar value, or null if it cannot be resolved to a simple scalar.
     */
    protected function resolveScalarValue(Node\Expr $expr): string|int|float|bool|null
    {
        if (
            $expr instanceof Node\Scalar\String_ ||
            $expr instanceof Node\Scalar\LNumber ||
            $expr instanceof Node\Scalar\DNumber
        ) {
            return $expr->value;
        }

        if ($expr instanceof Node\Expr\ConstFetch) {
            $name = strtolower((string) $expr->name);
            return match ($name) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => null, // Unknown constant or not a simple scalar constant
            };
        }

        return null; // Not a scalar expression this visitor can resolve
    }

    /**
     * Checks if an expression node represents a 'null' literal.
     *
     * @param Node\Expr $expr The expression node to check.
     * @return bool True if the expression is a null literal, false otherwise.
     */
    protected function isNullLiteral(Node\Expr $expr): bool
    {
        return $expr instanceof Node\Expr\ConstFetch &&
               strtolower((string) $expr->name) === 'null';
    }
}