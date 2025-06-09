<?php

namespace App\Services\Analyzers;

use App\Contracts\CIAnalyzerInterface;
use App\Enums\CIVersion;
use App\Services\Utility\PhpFileParser;
use App\Traits\HasDirectories;

abstract class AbstractCIAnalyzerService implements CIAnalyzerInterface
{
    use HasDirectories;

    public function __construct(
        protected PhpFileParser $phpFileParser
    ) {}

    abstract public function supports(CIVersion $version): bool;

    /**
     * Analyze the CodeIgniter project structure.
     * Each version-specific class may override this.
     */
    public function analyze(): array
    {
        return [
            'controllers' => $this->analyzeControllers(),
            'models'      => $this->analyzeModels(),
            'routes'      => $this->analyzeRoutes(),
            'libraries'   => $this->collectCustomFiles('libraries'),
            'helpers'     => $this->collectCustomFiles('helpers'),
        ];
    }

    abstract protected function getProcessorFactories(): array;

    protected function analyzeControllers(): array
    {
        $factories = $this->getProcessorFactories();
        return $this->parseWithProcessor('controllers', get_class($factories['controllers']), 'controller');
    }

    protected function analyzeModels(): array
    {
        $factories = $this->getProcessorFactories();
        return $this->parseWithProcessor('models', get_class($factories['models']), 'model');
    }

    protected function analyzeRoutes(): array
    {
        $factories = $this->getProcessorFactories();
        return $this->parseWithProcessor('controllers', get_class($factories['routes']), null, true);
    }

    protected function parseWithProcessor(string $type, string $processorClass, ?string $keyName = null, bool $flattenRoutes = false): array
    {
        $directory = $this->getCIProjectDirectory() . DIRECTORY_SEPARATOR . $type;
        $files = glob("{$directory}" . DIRECTORY_SEPARATOR . "*.php");

        $parseResult = $this->phpFileParser->parseFilesWithMapping(
            $files,
            fn() => new $processorClass(),
            function ($processor, $file) use ($keyName, $type) {
                $parsed = $processor->getResults();

                if ($keyName === null) {
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

        $result = [];

        if ($flattenRoutes) {
            foreach ($parseResult['success'] as $routes) {
                $result = array_merge($result, $routes);
            }
        } else {
            foreach ($parseResult['success'] as $item) {
                $result += $item;
            }
        }

        if (!empty($parseResult['errors'])) {
            $result['_errors'] = $parseResult['errors'];
        }

        return $result;
    }

    protected function collectCustomFiles(string $type): array
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
