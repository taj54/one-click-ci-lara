<?php

namespace App\Services\Parsers\NodeProcessors;

use App\Services\Parsers\AbstractConfigParserVisitor;
use App\Services\Parsers\Contracts\NodeProcessorInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Processes nodes specifically for CodeIgniter's database.php structure.
 */
class CIDatabaseNodeProcessor extends AbstractConfigParserVisitor implements NodeProcessorInterface
{
    public array $dbConfig = [];

    /**
     * Process a node, extracting database config values.
     *
     * @param Node $node The node to process.
     * @return void
     */
    public function process(Node $node): void
    {
        if (
            $node instanceof Expr\Assign &&
            $node->var instanceof Expr\ArrayDimFetch &&
            $node->var->var instanceof Expr\Variable &&
            $node->var->var->name === 'db' &&
            $node->var->dim instanceof Scalar\String_ &&
            $node->var->dim->value === 'default' &&
            $node->expr instanceof Expr\Array_
        ) {
            foreach ($node->expr->items as $item) {
                if (!$item->key instanceof Scalar\String_) {
                    continue;
                }

                /** @var Scalar\String_ $keyNode */
                $keyNode = $item->key;
                $key = $keyNode->value;
                $value = $this->resolveScalarValue($item->value);

                if ($value === null && !$this->isNullLiteral($item->value)) {
                    continue;
                }
                $this->dbConfig[$key] = $value;
            }
        }
    }

    /**
     * Get the extracted database config values.
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->dbConfig;
    }
}