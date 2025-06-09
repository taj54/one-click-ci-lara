<?php

namespace App\Services\Parsers\NodeProcessors\CI4;

use App\Services\Parsers\NodeProcessors\CIDatabaseNodeProcessorBase;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;

class CI4DatabaseNodeProcessor extends CIDatabaseNodeProcessorBase
{
    public function process(Node $node): void
    {
        if ($node instanceof Property) {
            foreach ($node->props as $prop) {
                $name = $prop->name->toString();
                if ($name === 'default') {
                    $this->dbConfig = array_merge(
                        $this->dbConfig,
                        $this->extractStringArrayItems($prop->default)
                    );
                }
            }
        }
    }
}
