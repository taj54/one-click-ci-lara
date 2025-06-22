<?php

namespace App\Services\Parsers\NodeProcessors;

use App\Services\Parsers\AbstractParserVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\PrettyPrinter\Standard;

abstract class CIControllerNodeProcessorBase extends AbstractParserVisitor
{
    protected string $className = '';
    protected string $parentClassName = '';
    protected array $methods = [];
    protected array $loadedDependencies = [];
    protected Standard $printer;

    public function __construct()
    {
        $this->printer = new Standard();
    }

    public function process(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->className = $node->name->name;
            $this->parentClassName = $node->extends?->toString() ?? null;
        }

        if ($node instanceof ClassMethod) {
            $methodName = $node->name->name;

            if ($methodName === '__construct') {
                $this->detectDependencyUsage($node);
                return;
            }

            $visibility = $this->getVisibility($node);
            $params = array_map(fn($param) => $param->var->name, $node->params);
            $usedDependencies = $this->detectDependencyUsage($node);
            $viewsUsed = $this->detectViewUsage($node);
            $summary = $this->printer->prettyPrint($node->stmts ?? []);

            $this->methods[] = [
                'name' => $methodName,
                'visibility' => $visibility,
                'params' => $params,
                'uses_model' => array_filter($usedDependencies, fn($d) => $d['type'] === 'model'),
                'uses_external' => array_filter($usedDependencies, fn($d) => $d['type'] !== 'model'),
                'views' => $viewsUsed,
                'summary' => "<?php\n" . $summary . "\n?>",
                'class' => $this->className,
                'extends' => $this->parentClassName,
            ];
        }
    }

    public function getResults(): array
    {
        return [
            'controller' => $this->className,
            'methods' => $this->methods,
        ];
    }

    protected function detectDependencyUsage(ClassMethod $method): array
    {
        $used = [];
        $stmts = $method->getStmts() ?? [];

        $this->traverseNodes($stmts, function (Node $node) use (&$used) {
            if (
                $node instanceof MethodCall &&
                $this->isLoadMethodCall($node, ['this' => ['load' => ['model', 'library', 'helper']]])
            ) {
                $loadType = $node->name->toString();
                $args = $node->args;

                if (!in_array($loadType, ['model', 'library', 'helper'], true) || empty($args)) {
                    return;
                }

                if ($loadType === 'helper') {
                    $arg = $args[0]->value ?? null;
                    $helperNames = [];

                    if ($arg instanceof Node\Expr\Array_) {
                        foreach ($arg->items as $item) {
                            $name = $this->getStringValue($item?->value);
                            if ($name) {
                                $helperNames[] = $name;
                            }
                        }
                    } elseif ($arg instanceof Node\Scalar\String_) {
                        $helperNames[] = $arg->value;
                    }

                    foreach ($helperNames as $helperName) {
                        $this->registerHelper($helperName);
                        $used[] = [
                            'type' => 'helper',
                            'alias' => $helperName,
                            'name' => $helperName,
                        ];
                    }

                    return;
                }

                $actualName = $this->getStringValue($args[0]->value ?? null);
                $alias = $this->getStringValue($args[1]->value ?? null) ?? $actualName;

                if ($actualName && $alias) {
                    $this->loadedDependencies[$alias] = [
                        'type' => $loadType,
                        'name' => $actualName,
                    ];
                }
            }
        });

        $this->traverseNodes($stmts, function (Node $node) use (&$used) {
            if (
                $node instanceof Node\Expr\PropertyFetch &&
                $node->var instanceof Node\Expr\Variable &&
                $node->var->name === 'this' &&
                $node->name instanceof Node\Identifier
            ) {
                $alias = $node->name->name;
                if (in_array($alias, ['load', 'input'], true)) {
                    return;
                }

                if (isset($this->loadedDependencies[$alias])) {
                    $dep = $this->loadedDependencies[$alias];
                    $used[] = [
                        'type' => $dep['type'],
                        'alias' => $alias,
                        'name' => $dep['name'],
                    ];
                } else {
                    $used[] = [
                        'type' => 'unknown',
                        'alias' => $alias,
                        'name' => null,
                    ];
                }
            }
        });

        $unique = [];
        foreach ($used as $entry) {
            $key = ($entry['name'] ?? '') . '|' . $entry['alias'];
            $unique[$key] = $entry;
        }

        return array_values($unique);
    }

    protected function detectViewUsage(ClassMethod $method): array
    {
        $viewsUsed = [];
        $stmts = $method->getStmts() ?? [];

        $this->traverseNodes($stmts, function ($node) use (&$viewsUsed) {
            if (
                $node instanceof MethodCall &&
                $this->isLoadMethodCall($node, ['this' => ['load' => ['view']]])
            ) {
                $args = $node->args;
                $viewName = $this->getStringValue($args[0]->value ?? null);
                if ($viewName) {
                    $viewsUsed[] = $viewName;
                }
            }
        });

        return array_unique($viewsUsed);
    }

    protected function registerHelper(string $name): void
    {
        $this->loadedDependencies[$name] = [
            'type' => 'helper',
            'name' => $name,
        ];
    }
}
