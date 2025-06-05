<?php

namespace App\Services\Parsers\Contracts;

use PhpParser\Node;

/**
 * Defines the contract for processing a PHP-Parser Node.
 */
interface NodeProcessorInterface
{
    /**
     * Process a given AST Node.
     *
     * @param Node $node The node to process.
     * @return void
     */
    public function process(Node $node): void;

    /**
     * Get the results of the processing.
     *
     * @return array
     */
    public function getResults(): array;
}