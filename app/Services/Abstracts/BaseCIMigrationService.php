<?php

namespace App\Services\Abstracts;

use App\Enums\CIVersion;
use App\Services\Coordinators\CIMigrationCoordinatorService;
use App\Traits\HasDirectories;
use App\Services\LaravelProjectSetupService;
use App\Services\LogService;
use App\Services\FileHandlerService;

abstract class BaseCIMigrationService
{
    use HasDirectories;

    protected ?string $injectedProjectName = null;
    protected ?string $injectedLaravelVersion = null;
    protected ?bool $injectedInstallSail = null;

    protected CIMigrationCoordinatorService $currentMigrationCoordinator;

    public function __construct(
        protected LogService $logService,
        protected LaravelProjectSetupService $laravelProjectSetupService,
        protected FileHandlerService $fileHandlerService,
        protected CIMigrationCoordinatorService $migrationCoordinator
    ) {}

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

    public function detectCodeIgniterVersion(): CIVersion
    {
        $version = $this->getCodeIgniterVersion();

        if ($version !== CIVersion::UNKNOWN) {
            return $version;
        }

        $version = $this->readVersionFromFile();
        $this->setCodeIgniterVersion($version);

        return $version;
    }

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

    public function setUserInputs(string $projectName, string $laravelVersion, bool $installSail): void
    {
        $this->injectedProjectName = $projectName;
        $this->injectedLaravelVersion = $laravelVersion;
        $this->injectedInstallSail = $installSail;
    }

    abstract protected function setupLaravelProject(): void;
}
