<?php

namespace App\Services;

class FileHandlerService
{
    use \App\Traits\HasDirectories;
    public $logService;

    /**
     * FileHandlerService constructor.
     */
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
        // $this->testEnvDir = rtrim('C:/Migration helper apps/test-environment', DIRECTORY_SEPARATOR);
    }


    /**
     * Check if the input directory is set (not empty).
     *
     * @return bool
     */
    public function emptyCheckInputDirectory()
    {
        if (empty($this->inputDirectory)) {
            $this->logService->error("Error: The --path option is required.");
            return false;
        }
        return true;
    }

    /**
     * Display the current input and output directory paths in the logService.
     *
     * @return void
     */
    public function displayDirectories()
    {
        $this->logService->info("\nInput Directory: {$this->inputDirectory}");
        $this->logService->info("Output Directory: {$this->outputDirectory}\n");
    }

    /**
     * Check if the input directory (and optionally a required file) exists.
     *
     * @param string $requiredFile Optional file to check for in the input directory.
     * @return bool
     */
    public function inputDirectoryPathCheck($requiredFile = '')
    {
        $result = $this->fileExistsInDirectory($this->inputDirectory, $requiredFile);

        if (!$result['exists']) {
            if (!empty($requiredFile)) {
                $this->logService->error("Error: The required file '{$requiredFile}' was not found at: {$result['expected_path']}");
            } else {
                $this->logService->error("Error: The specified input directory does not exist: {$result['expected_path']}");
            }
            return false;
        }

        // $this->logService->info("Input directory check passed: {$result['expected_path']}");
        return true;
    }

    /**
     * Check if a file or directory exists in the specified directory.
     *
     * @param string $directory
     * @param string $fileName
     * @return array
     */
    public function fileExistsInDirectory($directory, $fileName = '')
    {
        $expectedPath = rtrim($directory, DIRECTORY_SEPARATOR);
        if (!empty($fileName)) {
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
     *
     * @return bool
     */
    public function isInputDirectoryValid()
    {
        if (!is_dir($this->inputDirectory)) {
            $this->logService->error("Error: The specified input directory is not valid: {$this->inputDirectory}");
            return false;
        }
        return true;
    }

    /**
     * Ensure the output directory exists or can be created.
     *
     * @return bool
     */
    public function isOutputDirectoryMakeValid()
    {
        if (!$this->ensureOutputDirectoryExists()) {
            $this->logService->error("Error: Could not create output directory: {$this->outputDirectory}");
            return false;
        }
        return true;
    }

    /**
     * Create the output directory if it does not exist.
     *
     * @return bool
     */
    public function ensureOutputDirectoryExists()
    {
        $outputDir = $this->outputDirectory;

        // If directory exists, increment the last part
        while (is_dir($outputDir)) {
            $parts = explode(DIRECTORY_SEPARATOR, $outputDir);
            $last = array_pop($parts);

            // Check if last part ends with a number, increment it, else add _1
            if (preg_match('/^(.*?)(_(\d+))?$/', $last, $matches)) {
                $base = $matches[1];
                $num = isset($matches[3]) ? (int)$matches[3] + 1 : 1;
                $last = "{$base}_{$num}";
            }
            $parts[] = $last;
            $outputDir = implode(DIRECTORY_SEPARATOR, $parts);
        }

        $this->outputDirectory = $outputDir;
        return mkdir($this->outputDirectory,  0777, true);
    }
}
