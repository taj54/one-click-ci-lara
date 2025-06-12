<?php

namespace App\Services;

use App\Traits\HasDirectories;
use App\Traits\HasStatusBar;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str; // Assuming Laravel's Str helper is available

class LaravelProjectService
{
    use HasDirectories, HasStatusBar;
    protected  $logService;

    public function __construct(LogService $logService, StatusBarService $statusBarService)
    {
        $this->logService = $logService;
        $this->setStatusBar($statusBarService);
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
    public function createLaravelProject(string $projectName, string $laravelVersion, bool $installSail): void
    {
        $outputDir = $this->getOutputDirectory();
        $projectPath = $this->getLaravelProjectDirectory();

        if (!is_dir($outputDir)) {
            $this->logService->error("Output directory : '{$outputDir}' does not exist. Please create it or set a valid output directory.");
            return;
        }

        if (!$this->isComposerAvailable()) {
            $this->logService->error("Composer is not found in your system's PATH. Please install Composer or ensure it's accessible.");
            return;
        }

        $command = [
            'composer',
            'create-project',
            "laravel/laravel={$laravelVersion}",
            $projectName,
        ];

        $this->logService->comment("Attempting to create Laravel project '{$projectName}' in directory: {$outputDir} ");
        $this->statusBar()->start("Creating Laravel project '{$projectName}'...", 100);
        $onProgress = function ($buffer) {
            // Advance the progress bar by a small amount for each buffer received
            $this->statusBar()->advance('Installing dependencies...');
        };
        $isLaravelCreateProject = $this->runProcess($command, $outputDir, "Failed to create Laravel project '{$projectName}'", $onProgress, 10 * 60);

        if (!$isLaravelCreateProject) {
            $this->statusBar()->error('Failed to create Laravel project.');
            return;
        }
        $this->statusBar()->finish('Laravel project created.');
        $this->logService->info("Laravel project '{$projectName}' created successfully.");

        if ($installSail) {
            $this->installSail($projectPath);
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
        ];
        $this->statusBar()->start("Installing Laravel Sail...", 20);
        $onProgress = function ($buffer) {
            $this->statusBar()->advance('Running sail:install...');
        };

        $isSailInstallRun = $this->runProcess($command, $projectPath, "Failed to install Laravel Sail in '{$projectPath}'", $onProgress);

        if (!$isSailInstallRun) {
            $this->statusBar()->error('Failed to install Sail.');
            return; // Exit if Sail installation failed
        }
        $this->statusBar()->finish('Laravel Sail installed.');
        $this->logService->info("Laravel Sail installed successfully.");
    }

    /**
     * Executes a shell command using Symfony Process and handles logging.
     *
     * @param array $command The command and its arguments as an array.
     * @param string $cwd The working directory for the command.
     * @param string $failureMessage A message to log if the command fails.
     * @param int $timeout The timeout for the process in seconds.
     * @return bool True on success, false on failure.
     */
    protected function runProcess(array $command, string $cwd, string $failureMessage, ?callable $onProgress = null, int $timeout = 60)
    {
        $process = new Process($command, $cwd, ['PATH' => getenv('PATH') . ';' . env('PHP_PATH')], null, $timeout);

        $this->logService->comment("\nRunning command: " . implode(' ', $command) . " in directory: {$cwd}..");

        try {
            $process->run(function ($type, $buffer) use ($onProgress) {
                if ($onProgress) {
                    $onProgress($buffer);
                }
            });

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return true;
        } catch (ProcessFailedException $exception) {
            $this->logService->error(
                "{$failureMessage}.\n" .
                    "Command: " . $exception->getProcess()->getCommandLine() . "\n" .
                    "Working Directory: " . $cwd . "\n" .
                    "Error Output:\n" . $exception->getProcess()->getErrorOutput() . "\n" .
                    "Output:\n" . $exception->getProcess()->getOutput()
            );
            return false;
        } catch (\Exception $e) {
            $this->logService->error("An unexpected error occurred while running command: " . $e->getMessage());
            return false;
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
