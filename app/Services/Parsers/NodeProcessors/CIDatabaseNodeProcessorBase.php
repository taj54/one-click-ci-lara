<?php

namespace App\Services\Parsers\NodeProcessors;

use App\Contracts\NodeProcessorInterface;
use App\Services\Parsers\AbstractParserVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * Processes nodes specifically for CodeIgniter's database.php structure.
 */
class CIDatabaseNodeProcessorBase  extends AbstractParserVisitor implements NodeProcessorInterface
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
            $this->isNamedNestedArrayAssignment($node, 'db',  ['default'], Expr\Array_::class) 
        ) {
            $this->dbConfig = array_merge($this->dbConfig, $this->extractStringArrayItems($node->expr));
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
