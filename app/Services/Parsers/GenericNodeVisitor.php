<?php

namespace App\Services\Parsers;

use App\Contracts\NodeProcessorInterface;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * A generic PHP-Parser visitor that delegates node processing to a dedicated processor.
 */
class GenericNodeVisitor extends NodeVisitorAbstract
{
    protected NodeProcessorInterface $processor;

    public function __construct(NodeProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    public function enterNode(Node $node)
    {
        // Delegate the processing logic to the injected processor
        $this->processor->process($node);
    }

    /**
     * Get the processor instance, allowing access to its results.
     *
     * @return NodeProcessorInterface
     */
    public function getProcessor(): NodeProcessorInterface
    {
        return $this->processor;
    }
}