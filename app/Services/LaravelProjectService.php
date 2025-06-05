<?php

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str; // Assuming Laravel's Str helper is available

class LaravelProjectService
{
    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Creates a new Laravel project.
     *
     * @param string $outputDirectory The directory where the project will be created.
     * @param string $projectName The name of the Laravel project.
     * @param string $laravelVersion The specific Laravel version to install (e.g., "10.*").
     * @param bool $installSail Whether to install Laravel Sail after project creation.
     * @return void
     */
    public function createLaravelProject(string $outputDirectory, string $projectName, string $laravelVersion, bool $installSail): void
    {
        $outputDir = Str::finish($outputDirectory, DIRECTORY_SEPARATOR);
        $projectPath = $outputDir . $projectName;

        // 1. Ensure the output directory exists
        if (!is_dir($outputDir)) {
            $this->logService->error("Output directory '{$outputDir}' does not exist. Please create it or set a valid output directory.");
            return;
        }

        // 2. Check if Composer is available
        if (!$this->isComposerAvailable()) {
            $this->logService->error("Composer is not found in your system's PATH. Please install Composer or ensure it's accessible.");
            return;
        }

        // 3. Define the Composer command to create the project
        $command = [
            'composer',
            'create-project',
            "laravel/laravel={$laravelVersion}",
            $projectName,
            // '--prefer-dist', // Faster installation by downloading archives
            // '--no-interaction', // Do not ask any interactive questions
        ];

        $this->logService->comment("Attempting to create Laravel project '{$projectName}' in directory: {$outputDir}");

        // 4. Execute the Composer command
        // Modified to capture output even on success for better debugging
        list($success, $output, $errorOutput) = $this->runProcess($command, $outputDir, "Failed to create Laravel project '{$projectName}'",5*60);

        if (!$success) {
            // runProcess already logged the error, just return
            return;
        }

        $this->logService->info("Laravel project '{$projectName}' created successfully.");

        // 5. Verify project directory exists after creation
        if (!is_dir($projectPath)) {
            // If the directory is not found despite Composer reporting success, log detailed output
            $this->logService->error(
                "Project directory '{$projectPath}' not found after creation, " .
                    "despite Composer command reporting success. " .
                    "This might indicate a permissions issue, an external factor preventing directory creation, " .
                    "or an unusual Composer behavior.\n" .
                    "Composer Output:\n" . $output . "\n" .
                    "Composer Error Output:\n" . $errorOutput
            );
            return;
        }

        // 6. Optionally install Sail
        if ($installSail) {
            $this->installSail($projectPath); // Pass the actual project path
        }
    }

    /**
     * Installs Laravel Sail within the given project directory.
     *
     * @param string $projectPath The absolute path to the Laravel project directory.
     * @return void
     */
    protected function installSail(string $projectPath): void
    {
        $this->logService->comment("Installing Laravel Sail in '{$projectPath}'...");

        $command = [
            'php',
            'artisan',
            'sail:install',
            // '--with=mysql,redis,meilisearch,mailpit,selenium', // Example services, can be customized
            // '--no-interaction', // Do not ask any interactive questions
        ];

        // Ensure the process runs within the project directory
        // Modified to capture output even on success for better debugging
        list($success, $output, $errorOutput) = $this->runProcess($command, $projectPath, "Failed to install Laravel Sail in '{$projectPath}'",5*60);

        if (!$success) {
            // runProcess already logged the error, just return
            return; // Exit if Sail installation failed
        }

        $this->logService->info("Laravel Sail installed successfully.");
    }

    /**
     * Executes a shell command using Symfony Process and handles logging.
     *
     * @param array $command The command and its arguments as an array.
     * @param string $cwd The working directory for the command.
     * @param string $failureMessage A message to log if the command fails.
     * @param int $timeout The timeout for the process in seconds.
     * @return array Returns [bool success, string output, string errorOutput].
     */
    protected function runProcess(array $command, string $cwd, string $failureMessage, int $timeout = 60): array
    {
        $process = new Process($command, $cwd, null, null, $timeout);

        $this->logService->comment("Running command: " . implode(' ', $command) . " in directory: {$cwd}");

        try {
            $process->run(); // This will capture output internally

            if (!$process->isSuccessful()) {
                // If the process was not successful, throw an exception
                throw new ProcessFailedException($process);
            }

            // If successful, return true and the captured output
            return [true, $process->getOutput(), $process->getErrorOutput()];
        } catch (ProcessFailedException $exception) {
            // Log the detailed error from the exception
            $this->logService->error(
                "{$failureMessage}.\n" .
                    "Command: " . $exception->getProcess()->getCommandLine() . "\n" .
                    "Working Directory: " . $cwd . "\n" .
                    "Error Output:\n" . $exception->getProcess()->getErrorOutput() . "\n" .
                    "Output:\n" . $exception->getProcess()->getOutput()
            );
            // Return false and the captured output for the caller to handle
            return [false, $exception->getProcess()->getOutput(), $exception->getProcess()->getErrorOutput()];
        } catch (\Exception $e) {
            // Catch any other unexpected exceptions
            $this->logService->error("An unexpected error occurred while running command: " . $e->getMessage());
            return [false, '', 'An unexpected error occurred: ' . $e->getMessage()];
        }
    }

    /**
     * Checks if Composer is available in the system's PATH.
     *
     * @return bool True if Composer is found, false otherwise.
     */
    protected function isComposerAvailable(): bool
    {
        $composerPath = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? shell_exec('where composer 2> NUL') // Redirect stderr to NUL on Windows
            : shell_exec('which composer 2>/dev/null'); // Redirect stderr to /dev/null on Unix-like systems

        return !empty(trim($composerPath));
    }
}
