<?php

namespace App\Services\Parsers\NodeProcessors;

use App\Services\Parsers\AbstractParserVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\PrettyPrinter\Standard;

abstract class CIModelNodeProcessorBase extends AbstractParserVisitor 
{
    protected string $className = '';
    protected string $parentClassName = '';
    protected array $methods = [];
    protected array $usedTables = [];
    protected Standard $printer;

    public function __construct()
    {
        $this->printer = new Standard();
    }

    public function process(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->className = $node->name->name;
            $this->parentClassName = $node->extends?->toString() ?? '';
        }

        if ($node instanceof ClassMethod) {
            $methodName = $node->name->name;
            $visibility = $this->getVisibility($node);
            $params = array_map(fn($param) => $param->var->name, $node->params);
            $tables = $this->detectUsedTables($node);
            $summary = $this->printer->prettyPrint($node->stmts ?? []);

            $this->methods[] = [
                'name' => $methodName,
                'visibility' => $visibility,
                'params' => $params,
                'used_tables' => $tables,
                'summary' => "<?php\n" . $summary . "\n?>",
                'class' => $this->className,
                'extends' => $this->parentClassName,
            ];

            $this->usedTables = array_merge($this->usedTables, $tables);
        }
    }

    public function getResults(): array
    {
        return [
            'model' => $this->className,
            'extends' => $this->parentClassName,
            'methods' => $this->methods,
            'used_tables' => array_unique($this->usedTables),
        ];
    }

    protected function detectUsedTables(ClassMethod $method): array
    {
        $tables = [];
        $stmts = $method->getStmts() ?? [];

        $this->traverseNodes($stmts, function (Node $node) use (&$tables) {
            if (
                $node instanceof MethodCall &&
                $this->isLoadMethodCall($node, ['this' => ['db' => ['get', 'insert', 'get_where']]]) &&
                $node->args[0]->value instanceof Node\Scalar\String_
            ) {
                $tables[] = $node->args[0]->value->value;
            }
        });

        return array_unique($tables);
    }
}
