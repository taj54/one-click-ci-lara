<?php

namespace App\Services\Parsers\NodeProcessors;

use App\Services\Parsers\AbstractConfigParserVisitor; 
use App\Contracts\NodeProcessorInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Processes nodes specifically for CodeIgniter's config.php structure.
 */
class CIConfigNodeProcessor extends AbstractConfigParserVisitor implements NodeProcessorInterface
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
            $node->var instanceof Expr\ArrayDimFetch &&
            $node->var->var instanceof Expr\Variable &&
            $node->var->var->name === 'config' &&
            $node->var->dim instanceof Scalar\String_
        ) {
            $key = $node->var->dim->value;
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