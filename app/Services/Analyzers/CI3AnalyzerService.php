<?php

namespace App\Services\Analyzers;

use App\Contracts\CIAnalyzerInterface;
use App\Enums\CIVersion;
use App\Services\Parsers\NodeProcessors\CIControllerNodeProcessor;
use App\Services\Parsers\NodeProcessors\CIModelNodeProcessor;
use App\Services\Parsers\NodeProcessors\CIRouteNodeProcessor;
use App\Services\Utility\PhpFileParser;
use App\Traits\HasDirectories;

class CI3AnalyzerService implements CIAnalyzerInterface
{
    use HasDirectories;

    public function __construct(
        private PhpFileParser $phpFileParser
    ) {}

    /**
     * Checks if this analyzer supports the given CodeIgniter version.
     */
    public function supports(CIVersion $version): bool
    {
        return $version === CIVersion::CI3;
    }

    /**
     * Analyze the CodeIgniter 3 project structure and extract relevant info.
     *
     * @return array Analysis results (controllers, models, routes, libraries, helpers)
     */
    public function analyze(): array
    {
        return [
            'controllers' => $this->parseWithProcessor('controllers', CIControllerNodeProcessor::class, 'controller'),
            'models'      => $this->parseWithProcessor('models', CIModelNodeProcessor::class, 'model'),
            'routes'      => $this->parseWithProcessor('controllers', CIRouteNodeProcessor::class, null, true),
            'libraries'   => $this->collectCustomFiles('libraries'),
            'helpers'     => $this->collectCustomFiles('helpers'),
        ];
    }

    /**
     * Generic parser that processes PHP files in a directory using the given processor.
     *
     * @param string $type Directory name (e.g., 'controllers', 'models')
     * @param string $processorClass NodeProcessor class name to instantiate
     * @param string|null $keyName The key name to extract from processor results; null for route-style flat arrays
     * @param bool $flattenRoutes If true, return merged flat array (for routes)
     * @return array Parsed results
     */
    private function parseWithProcessor(string $type, string $processorClass, ?string $keyName = null, bool $flattenRoutes = false): array
    {
        $directory = $this->getCIProjectDirectory() . DIRECTORY_SEPARATOR . $type;
        $files = glob("{$directory}" . DIRECTORY_SEPARATOR . "*.php");

        $parseResult = $this->phpFileParser->parseFilesWithMapping(
            $files,
            fn() => new $processorClass(),
            function ($processor, $file) use ($keyName, $type) {
                $parsed = $processor->getResults();

                if ($keyName === null) {
                    // For routes (flattened)
                    return $parsed;
                }

                $name = $parsed[$keyName] ?? basename($file, '.php');
                $entry = ['file' => $file];

                if ($type === 'models') {
                    $entry['extends'] = $parsed['extends'] ?? null;
                }
                $entry['methods'] = $parsed['methods'] ?? [];

                return [$name => $entry];
            }
        );

        if ($flattenRoutes) {
            $result = [];
            foreach ($parseResult['success'] as $routes) {
                $result = array_merge($result, $routes);
            }
        } else {
            $result = [];
            foreach ($parseResult['success'] as $item) {
                $result += $item;
            }
        }

        if (!empty($parseResult['errors'])) {
            $result['_errors'] = $parseResult['errors'];
        }

        return $result;
    }

    private function collectCustomFiles(string $type): array
    {
        $path =  $this->getCIProjectDirectory() . DIRECTORY_SEPARATOR . "{$type}";
        $files = [];

        foreach (glob("$path" . DIRECTORY_SEPARATOR . "*.php") as $file) {
            $name = basename($file, '.php');
            $files[$name] = ['file' => $file];
        }

        return $files;
    }
}
