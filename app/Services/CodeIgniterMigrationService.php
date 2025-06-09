<?php

namespace App\Services;

use App\Enums\CIVersion;
use App\Services\Coordinators\CIMigrationCoordinatorService;
use App\Traits\HasDirectories;

class CodeIgniterMigrationService
{
    use HasDirectories;

    private  $currentMigrationCoordinator;

    public function __construct(
        private PromptService $promptService,
        private LogService $logService,
        private LaravelProjectSetupService $laravelProjectSetupService,
        private FileHandlerService $fileHandlerService,
        private CIMigrationCoordinatorService $migrationCoordinator
    ) {}

    /**
     * Initiates the CodeIgniter to Laravel migration process.
     */
    public function migrate(): bool
    {
        $version = $this->detectCodeIgniterVersion();

        if ($version === CIVersion::UNKNOWN) {
            $this->logService->error("Could not determine CodeIgniter version or unsupported version.");
            return false;
        }

        $this->logService->info("CodeIgniter version detected: {$version->value}");

        $this->currentMigrationCoordinator = $this->migrationCoordinator;

        if (!$this->currentMigrationCoordinator->supports($version)) {
            $this->logService->error("No migration coordinator found for CodeIgniter version: {$version->value}");
            return false;
        }

        $this->logService->info("Initiating {$version->label()} migration...");

        $this->setupLaravelProject();

        $report = $this->currentMigrationCoordinator->executeMigration();

        return $this->handleMigrationReport($report);
    }

    /**
     * Detects the CodeIgniter version.
     */
    protected function detectCodeIgniterVersion(): CIVersion
    {
        $version = $this->getCodeIgniterVersion();

        if ($version !== CIVersion::UNKNOWN) {
            return $version;
        }

        $version = $this->readVersionFromFile();
        $this->setCodeIgniterVersion($version);

        return $version;
    }

    /**
     * Reads the CodeIgniter version based on the project file structure.
     */
    protected function readVersionFromFile(): CIVersion
    {
        $core = $this->getCICoreDirectory();
        $compat = $this->getCICompatDirectory();
        $system = $this->getCISystemDirectory();

        if (is_dir($core) && !is_dir($compat)) {
            $content = @file_get_contents($core . DIRECTORY_SEPARATOR . 'CodeIgniter.php');
            if ($content !== false && str_contains($content, 'CI_VERSION')) {
                return CIVersion::CI2;
            }
        }

        if (is_dir($core) && is_dir($compat)) {
            return CIVersion::CI3;
        }

        if (file_exists($system . DIRECTORY_SEPARATOR . 'bootstrap.php') && !is_dir($core)) {
            return CIVersion::CI4;
        }

        return CIVersion::UNKNOWN;
    }

    /**
     * Sets up a Laravel project based on user input.
     */
    protected function setupLaravelProject(): void
    {
        $projectName = $this->promptService->promptForProjectName();
        $laravelVersion = $this->promptService->promptForLaravelVersion();
        $installSail = $this->promptService->promptForSailInstall();

        $this->logService->info("Selected Laravel Version: {$laravelVersion}");

        $this->laravelProjectSetupService->setLaravelProjectName($projectName);
        $this->laravelProjectSetupService->createAndSetupProject(
            $projectName,
            $laravelVersion,
            $installSail
        );
    }

    /**
     * Processes the result of the migration report.
     */
    protected function handleMigrationReport(array $report): bool
    {
        if (!empty($report['conversion']['success'])) {
            $this->logService->info("Migration successful.");
            return true;
        }

        $error = $report['conversion']['error'] ?? 'Unknown error during migration.';
        $this->logService->error($error);

        return false;
    }
}
