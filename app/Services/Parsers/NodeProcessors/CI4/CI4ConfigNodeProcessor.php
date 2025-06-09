<?php

namespace App\Services\Parsers\NodeProcessors\CI4;

use App\Services\Parsers\NodeProcessors\CIConfigNodeProcessorBase;
use PhpParser\Node;

class CI4ConfigNodeProcessor extends CIConfigNodeProcessorBase
{
    public function process(Node $node): void
    {
        if ($node instanceof \PhpParser\Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $name = $prop->name->toString();
                $value = $this->resolveScalarValue($prop->default);
                if ($value !== null || $this->isNullLiteral($prop->default)) {
                    $this->ciConfig[$name] = $value;
                }
            }
        }
    }
}
