<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CodeIgniterMigrationService;
use App\Services\FileHandlerService;
use App\Services\LogService;
use App\Services\StatusBarService;

class MigrateCodeIgniterApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taj-migrate:ci {--path=} {--output-dir=.: Output directory (default: current directory)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates a CodeIgniter application to Laravel.';

    public function __construct(
        protected CodeIgniterMigrationService $migrationService,
        protected FileHandlerService $fileHandlerService,
        protected LogService $logService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $testEnvDir = rtrim('C:/Migration helper apps/test-environment', DIRECTORY_SEPARATOR);
        $inputDirectory = rtrim($this->option('path'), DIRECTORY_SEPARATOR);
        $outputDirectory = rtrim($this->option('output-dir'), DIRECTORY_SEPARATOR);

        // Setup file handler
        $this->setupFileHandler($testEnvDir, $inputDirectory, $outputDirectory);



        // Validate input/output directories
        if (!$this->validateDirectories()) {
            return self::FAILURE;
        }

        // Run migration
        return $this->migrationService->migrate()
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * Set up file handler service with environment and paths.
     */
    protected function setupFileHandler(string $envDir, string $input, string $output): void
    {
        $this->fileHandlerService->setTestEnvDir($envDir);
        $this->fileHandlerService->setInputDirectory($input);
        $this->fileHandlerService->setOutputDirectory($output);
        // Optionally pass CLI context to service (if needed)
        // $this->fileHandlerService->setConsole($this);
    }

    /**
     * Validate required input and output directories.
     */
    protected function validateDirectories(): bool
    {
        return $this->fileHandlerService->emptyCheckInputDirectory()
            && $this->fileHandlerService->inputDirectoryPathCheck()
            && $this->fileHandlerService->isInputDirectoryValid()
            && $this->fileHandlerService->isOutputDirectoryMakeValid();
    }
}
