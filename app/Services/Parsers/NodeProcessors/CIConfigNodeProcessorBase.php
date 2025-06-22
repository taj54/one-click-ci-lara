<?php

namespace App\Services\Parsers\NodeProcessors;

use App\Services\Parsers\AbstractParserVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * Base config processor shared by all CI versions.
 */
abstract class CIConfigNodeProcessorBase  extends AbstractParserVisitor 
{
    public array $ciConfig = [];

    /**
     * Process a node, extracting config values.
     *
     * @param Node $node The node to process.
     * @return void
     */
    public function process(Node $node): void
    {
        // Look for assignments to $config['key'] = value;
        if (
            $node instanceof Expr\Assign &&
            $this->isNamedArrayAssignment($node, 'config')
        ) {
            $key = $this->getArrayDimValue($node);
            if ($key === null) {
                // fail safe
                return;
            }
            $value = $this->resolveScalarValue($node->expr);
            if ($value !== null || $this->isNullLiteral($node->expr)) {
                $this->ciConfig[$key] = $value;
            }
        }
    }

    /**
     * Get the extracted config values.
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->ciConfig;
    }
}
