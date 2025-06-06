<?php

namespace App\Services;

use App\Traits\HasDirectories;

class FileHandlerService
{
    use HasDirectories;

    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Check if the input directory is set (not empty).
     */
    public function emptyCheckInputDirectory(): bool
    {
        if (empty($this->getOutputDirectory())) {
            $this->logService->error("Error: The --path option is required.");
            return false;
        }
        return true;
    }

    /**
     * Display the current input and output directory paths.
     */
    public function displayDirectories(): void
    {
        $this->logService->info("\nInput Directory: {$this->getInputDirectory()}");
        $this->logService->info("Output Directory: {$this->getOutputDirectory()}\n");
    }

    /**
     * Check if the input directory exists, and optionally a required file within it.
     */
    public function inputDirectoryPathCheck(string $requiredFile = ''): bool
    {
        $result = $this->fileExistsInDirectory($this->getInputDirectory(), $requiredFile);

        if (!$result['exists']) {
            if ($requiredFile !== '') {
                $this->logService->error("Error: The required file '{$requiredFile}' was not found at: {$result['expected_path']}");
            } else {
                $this->logService->error("Error: The specified input directory does not exist: {$result['expected_path']}");
            }
            return false;
        }

        return true;
    }

    /**
     * Check if a file or directory exists in the specified directory.
     *
     * @return array{type: string, folder: string, file: string, expected_path: string, exists: bool}
     */
    public function fileExistsInDirectory(string $directory, string $fileName = ''): array
    {
        $expectedPath = rtrim($directory, DIRECTORY_SEPARATOR);
        if ($fileName !== '') {
            $expectedPath .= DIRECTORY_SEPARATOR . $fileName;
        }
        $exists = file_exists($expectedPath);

        return [
            'type' => is_dir($expectedPath) ? 'directory' : 'file',
            'folder' => $directory,
            'file' => $fileName,
            'expected_path' => $expectedPath,
            'exists' => $exists,
        ];
    }

    /**
     * Validate that the input directory exists and is a directory.
     */
    public function isInputDirectoryValid(): bool
    {
        if (!is_dir($this->getInputDirectory())) {
            $this->logService->error("Error: The specified input directory is not valid: {$this->getInputDirectory()}");
            return false;
        }
        return true;
    }

    /**
     * Ensure the output directory exists or can be created.
     */
    public function isOutputDirectoryMakeValid(): bool
    {
        if (!$this->ensureOutputDirectoryExists()) {
            $this->logService->error("Error: Could not create output directory: {$this->getOutputDirectory()}");
            return false;
        }
        return true;
    }

    /**
     * Create the output directory if it does not exist.
     * If directory exists, auto-increment the last part until an available directory is found.
     */
    public function ensureOutputDirectoryExists(): bool
    {
        $outputDir = $this->getOutputDirectory();

        while (is_dir($outputDir)) {
            $parts = explode(DIRECTORY_SEPARATOR, $outputDir);
            $last = array_pop($parts);

            if (preg_match('/^(.*?)(_(\d+))?$/', $last, $matches)) {
                $base = $matches[1];
                $num = isset($matches[3]) ? ((int)$matches[3] + 1) : 1;
                $last = "{$base}_{$num}";
            }
            $parts[] = $last;
            $outputDir = implode(DIRECTORY_SEPARATOR, $parts);
        }
        // Update the outputDirectory property with the new path
        $this->setOutputDirectory($outputDir);


        return mkdir($outputDir, 0777, true);
    }
}
