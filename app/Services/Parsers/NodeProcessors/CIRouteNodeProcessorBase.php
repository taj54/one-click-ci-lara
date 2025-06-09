<?php

namespace App\Services\Parsers\NodeProcessors;

use App\Contracts\NodeProcessorInterface;
use App\Services\Parsers\AbstractParserVisitor;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;


/**
 * Base route processor for CI controllers.
 */

class CIRouteNodeProcessorBase  extends AbstractParserVisitor implements NodeProcessorInterface
{
    private string $controllerName = '';
    private array $routes = [];

    public function process(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->controllerName = $node->name->toString();
        }

        if ($node instanceof ClassMethod) {
            $methodName = $node->name->toString();

            // Skip if not public or if method is constructor by name check
            if (!$node->isPublic() || $methodName === '__construct') {
                return;
            }

            $httpVerb = $this->guessHttpVerb($methodName);

            if ($this->controllerName && $methodName) {
                $this->routes[] = [
                    'uri' => strtolower($this->controllerName) . '/' . strtolower($methodName),
                    'controller' => $this->controllerName,
                    'method' => $methodName,
                    'http' => $httpVerb,
                ];
            }
        }
    }

    public function getResults(): array
    {
        return $this->routes;
    }

    private function guessHttpVerb(string $method): string
    {
        return match (true) {
            str_starts_with($method, 'get') => 'GET',
            str_starts_with($method, 'post'),
            str_starts_with($method, 'store'),
            str_starts_with($method, 'create') => 'POST',
            str_starts_with($method, 'put'),
            str_starts_with($method, 'update') => 'PUT',
            str_starts_with($method, 'delete') => 'DELETE',
            default => 'GET', // fallback if we can't guess
        };
    }
}
