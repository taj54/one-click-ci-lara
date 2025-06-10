<?php

namespace App\Console\Commands;

use App\Services\CLI\CLIMigrationService;
use Illuminate\Console\Command;
use App\Services\FileHandlerService;
use App\Services\LogService;

class MigrateCodeIgniterApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taj-migrate:ci {--path=}   {--output-dir=version : Output directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates a CodeIgniter application to Laravel.';

    public function __construct(
        protected CLIMigrationService $migrationService,
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

        $inputDirectory = rtrim($this->option('path'), DIRECTORY_SEPARATOR);
        $outputDirectory = rtrim($this->option('output-dir'), DIRECTORY_SEPARATOR);
        // Setup file handler
        $this->setupFileHandler($inputDirectory, $outputDirectory);



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
    protected function setupFileHandler(string $input, string $output): void
    {
        $appPath = base_path();
        $testEnvDirectory = realpath($appPath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'test-environment' . DIRECTORY_SEPARATOR);
        $this->fileHandlerService->setTestEnvDir($testEnvDirectory);
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
