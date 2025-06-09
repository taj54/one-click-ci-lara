<?php

namespace App\Services\Utility;

use App\Contracts\NodeProcessorInterface;
use App\Services\Parsers\GenericNodeVisitor;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use RuntimeException;

/**
 * Utility class for parsing PHP files using PHP-Parser and a given NodeProcessor.
 */
class PhpFileParser
{
    /**
     * Parses a PHP file using a provided NodeProcessor and returns the processor instance
     * containing the collected results.
     *
     * @param string $filePath The path to the PHP file to parse.
     * @param NodeProcessorInterface $processor An instance of a processor to use for node handling.
     * @return NodeProcessorInterface The processor instance after traversal, containing the results.
     * @throws RuntimeException If the file cannot be read or parsing fails.
     */
    public function parse(string $filePath, NodeProcessorInterface $processor): NodeProcessorInterface
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        $code = file_get_contents($filePath);
        if ($code === false) {
            throw new RuntimeException("Failed to read file: $filePath");
        }

        try {
            $parser = (new ParserFactory())->createForHostVersion(); //their is other option to select particular version
            $ast = $parser->parse($code);

            $visitor = new GenericNodeVisitor($processor); // Use the generic visitor with the specific processor

            $traverser = new NodeTraverser();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            return $visitor->getProcessor(); // Return the processor which holds the results
        } catch (\Throwable $e) {
            // Catch any throwable, including ParserError, and wrap it in a RuntimeException
            throw new RuntimeException("Error parsing PHP file '$filePath': " . $e->getMessage(), 0, $e);
        }
    }
    /**
     * Parses multiple files and applies a callable to each processor's results to transform them.
     *
     * @param string[] $filePaths
     * @param callable(): NodeProcessorInterface $processorFactory
     * @param callable(NodeProcessorInterface, string): mixed|null $resultMapper Optional function to map processor results per file
     *
     * @return array{
     *     success: array<string, mixed>,
     *     errors: array<string, string>
     * }
     */
    public function parseFilesWithMapping(array $filePaths, callable $processorFactory, ?callable $resultMapper = null): array
    {
        $success = [];
        $errors = [];

        foreach ($filePaths as $filePath) {
            try {
                $processor = $processorFactory();
                $this->parse($filePath, $processor);
                $mappedResult = $resultMapper ? $resultMapper($processor, $filePath) : $processor;
                $success[$filePath] = $mappedResult;
            } catch (\Throwable $e) {
                $errors[$filePath] = $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'errors' => $errors,
        ];
    }
}
