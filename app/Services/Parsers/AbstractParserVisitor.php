<?php

namespace App\Services\Parsers;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar;
use PhpParser\Node\Expr;

abstract class AbstractParserVisitor
{
    /**
     * Recursively traverse nodes and apply a callback.
     *
     * @param Node[] $nodes
     * @param callable(Node $node): void $callback
     * @return void
     */
    protected function traverseNodes(array $nodes, callable $callback): void
    {
        foreach ($nodes as $node) {
            $callback($node);

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->$subNodeName;

                if (is_array($subNode)) {
                    $this->traverseNodes($subNode, $callback);
                } elseif ($subNode instanceof Node) {
                    $this->traverseNodes([$subNode], $callback);
                }
            }
        }
    }

    /**
     * Resolves a scalar value from expression nodes (string, int, float, bool, null).
     * Returns null if cannot be resolved.
     *
     * @param Expr $expr
     * @return string|int|float|bool|null
     */
    protected function resolveScalarValue(Expr $expr): string|int|float|bool|null
    {
        if (
            $expr instanceof Scalar\String_ ||
            $expr instanceof Scalar\LNumber ||
            $expr instanceof Scalar\DNumber
        ) {
            return $expr->value;
        }

        if ($expr instanceof Expr\ConstFetch) {
            $name = strtolower((string) $expr->name);
            return match ($name) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => null,
            };
        }

        return null;
    }

    /**
     * Checks if an expression node represents a 'null' literal.
     *
     * @param Expr $expr
     * @return bool
     */
    protected function isNullLiteral(Expr $expr): bool
    {
        return $expr instanceof Expr\ConstFetch
            && strtolower((string) $expr->name) === 'null';
    }

    /**
     * Returns visibility string for ClassMethod or ClassProperty nodes.
     *
     * @param Node\Stmt\ClassMethod|Node\Stmt\Property $node
     * @return string
     */
    protected function getVisibility(Node $node): string
    {
        if (method_exists($node, 'isPublic') && $node->isPublic()) {
            return 'public';
        }
        if (method_exists($node, 'isProtected') && $node->isProtected()) {
            return 'protected';
        }
        return 'private';
    }

    /**
     * Extracts string value from a Node\Scalar\String_ or returns null.
     * 
     * @param Node|null $node
     * @return string|null
     */
    protected function getStringValue(?Node $node): ?string
    {
        if ($node instanceof Scalar\String_) {
            return $node->value;
        }
        return null;
    }

    /**
     * Utility to safely get node's subnode or null.
     *
     * @param Node $node
     * @param string $subNodeName
     * @return Node|null
     */
    protected function getSubNode(Node $node, string $subNodeName): ?Node
    {
        return $node->$subNodeName ?? null;
    }
    /**
     * Determines if a node is an assignment like `$variable['key'] = value`
     *
     * @param Node $node
     * @param string $varName The variable name (e.g., 'config', 'db')
     * @param string|null $arrayKey Optional array key to match (e.g., 'default')
     * @param string|null $expectedExprClass Optional class name of expected expression (e.g., Expr\Array_::class)
     * @return bool
     */
    protected function isNamedArrayAssignment(
        Node $node,
        string $varName,
        ?string $arrayKey = null,
        ?string $expectedExprClass = null
    ): bool {
        if (
            !$node instanceof Expr\Assign ||
            !$node->var instanceof Expr\ArrayDimFetch ||
            !$node->var->var instanceof Expr\Variable ||
            $node->var->var->name !== $varName ||
            !$node->var->dim instanceof Scalar\String_
        ) {
            return false;
        }

        if ($arrayKey !== null && $node->var->dim->value !== $arrayKey) {
            return false;
        }

        if ($expectedExprClass !== null && !$node->expr instanceof $expectedExprClass) {
            return false;
        }

        return true;
    }
    protected function isNamedNestedArrayAssignment(
        Node $node,
        string $baseVarName,
        array $keyPath,
        ?string $expectedExprClass = null
    ): bool {
        if (!$node instanceof Expr\Assign) {
            return false;
        }

        // Start from the innermost ArrayDimFetch (i.e., the left-hand side of the assignment)
        $current = $node->var;

        // Traverse keyPath from end to beginning
        for ($i = count($keyPath) - 1; $i >= 0; $i--) {
            if (!$current instanceof Expr\ArrayDimFetch) {
                return false;
            }

            $expectedKey = $keyPath[$i];
            $dim = $current->dim;

            if (!$dim instanceof Scalar\String_ || $dim->value !== $expectedKey) {
                return false;
            }

            $current = $current->var;
        }

        // At the end of the key chain, we should be looking at the base variable (e.g., $db)
        if (!$current instanceof Expr\Variable || $current->name !== $baseVarName) {
            return false;
        }

        if ($expectedExprClass !== null && !$node->expr instanceof $expectedExprClass) {
            return false;
        }

        return true;
    }


    /**
     * Get the array key string from a named array assignment node.
     *
     * @param Node $node
     * @return string|null Returns the array key if available and valid, null otherwise.
     */
    protected function getArrayDimValue(Node $node): ?string
    {
        if (
            $node instanceof Expr\Assign &&
            $node->var instanceof Expr\ArrayDimFetch &&
            $node->var->dim instanceof Scalar\String_
        ) {
            return $node->var->dim->value;
        }

        return null;
    }
    protected function extractStringArrayItems(Expr\Array_ $array): array
    {
        $results = [];

        foreach ($array->items as $item) {
            $keyNode = $item->key;

            if (!($keyNode instanceof Scalar\String_)) {
                continue;
            }

            $key = $keyNode->value;
            $value = $this->resolveScalarValue($item->value);

            if ($value === null && !$this->isNullLiteral($item->value)) {
                continue;
            }

            $results[$key] = $value;
        }

        return $results;
    }
    protected function isLoadMethodCall(Node $node, array $allowedCalls = []): bool
    {
        if (
            !$node instanceof Expr\MethodCall ||
            !$node->var instanceof Expr\PropertyFetch ||
            !$node->var->name instanceof Identifier ||
            !$node->name instanceof Identifier
        ) {
            return false;
        }

        $chain = [];

        // Walk the chain: $this->load->library becomes ['this', 'load', 'library']
        $current = $node;
        while ($current instanceof Expr\MethodCall || $current instanceof Expr\PropertyFetch) {
            if ($current->name instanceof Identifier) {
                array_unshift($chain, $current->name->toString());
            }

            $current = $current->var ?? null;
        }

        if ($current instanceof Expr\Variable && is_string($current->name)) {
            array_unshift($chain, $current->name);
        }

        return $this->matchesNestedAllowedCall($chain, $allowedCalls);
    }

    protected function matchesNestedAllowedCall(array $chain, array $allowedCalls): bool
    {
        $current = array_shift($chain);

        if (!isset($allowedCalls[$current])) {
            return false;
        }

        $next = $allowedCalls[$current];

        // If it's a list (terminal methods): match directly
        if (is_array($next) && array_is_list($next)) {
            return in_array($chain[0] ?? '', $next, true);
        }

        // Otherwise, recurse deeper
        if (!empty($chain)) {
            return $this->matchesNestedAllowedCall($chain, $next);
        }

        return false;
    }
}
