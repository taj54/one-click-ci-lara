<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CodeIgniterMigrationService;
use App\Services\FileHandlerService;
use App\Services\LogService;

class MigrateCodeIgniterApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taj-migrate:ci {--path=} {--output-dir=.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates a CodeIgniter application to Laravel.';

    protected $migrationService;
    protected $fileHandlerService;
    protected $logService;

    public function __construct(
        CodeIgniterMigrationService $migrationService,
        FileHandlerService $fileHandlerService,
        LogService $logService // Add this
    ) {
        parent::__construct();
        $this->migrationService = $migrationService;
        $this->fileHandlerService = $fileHandlerService;
        $this->logService = $logService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $testEnvDir = rtrim('C:/Migration helper apps/test-environment/', DIRECTORY_SEPARATOR);
        $inputDirectory = rtrim($this->option('path'), DIRECTORY_SEPARATOR);
        $outputDirectory = rtrim($this->option('output-dir'), DIRECTORY_SEPARATOR);

        // Set up log service with console
        // Set up file handler with all directories and console
        // $this->fileHandlerService->console = $this;
        $this->fileHandlerService->setTestEnvDir($testEnvDir);
        $this->fileHandlerService->setInputDirectory($inputDirectory);
        $this->fileHandlerService->setOutputDirectory($outputDirectory);

        // Validate all directories in one go
        if (
            !$this->fileHandlerService->emptyCheckInputDirectory() ||
            !$this->fileHandlerService->inputDirectoryPathCheck() ||
            !$this->fileHandlerService->isInputDirectoryValid() ||
            !$this->fileHandlerService->isOutputDirectoryMakeValid()
        ) {
            return self::FAILURE;
        }


        // Set up migration service with required dependencies
        $this->migrationService->setInputDirectory($this->fileHandlerService->getInputDirectory());
        $this->migrationService->setOutputDirectory($this->fileHandlerService->getOutputDirectory());

        // Run migration
        if (!$this->migrationService->migrate()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
