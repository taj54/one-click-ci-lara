<?php

namespace App\Services;

use App\Contracts\CodeIgniterConverterInterface;
use App\Enums\CIVersion;
use App\Services\Converters\CI3ConverterService;
use App\Traits\HasDirectories;
use Illuminate\Container\Attributes\Log;

class CodeIgniterMigrationService
{
    use HasDirectories;

    protected LogService $logService;
    protected PromptService $promptService;
    protected LaravelProjectSetupService $laravelProjectSetupService;
    protected FileHandlerService $fileHandlerService;
    /**
     * @var iterable<CodeIgniterConverterInterface>
     */
    protected iterable $converters;
    protected ?CodeIgniterConverterInterface $currentConverter = null; // To store the resolved converter

    public function __construct(
        PromptService $promptService,
        LogService $logService, // Type-hinting to interface
        LaravelProjectSetupService $laravelProjectSetupService,
        FileHandlerService $fileHandlerService,
        iterable $converters
    ) {
        $this->promptService = $promptService;
        $this->logService = $logService;
        $this->laravelProjectSetupService = $laravelProjectSetupService;
        $this->fileHandlerService = $fileHandlerService;
        $this->converters = $converters;
    }

    /**
     * Resolves the appropriate converter for the given CodeIgniter version.
     *
     * @param CIVersion $version The CodeIgniter version.
     * @return CodeIgniterConverterInterface|null The converter instance, or null if not found.
     */
    protected function resolveConverterFor(CIVersion $version): ?CodeIgniterConverterInterface
    {
        foreach ($this->converters as $converter) {
            // Ensure the item is an instance of the interface before calling supports
            if ($converter instanceof CodeIgniterConverterInterface && $converter->supports($version)) {
                return $converter;
            }
        }

        return null;
    }

    /**
     * Initiates the CodeIgniter to Laravel migration process.
     *
     * @return bool True if migration process started successfully, false otherwise.
     */
    public function migrate(): bool
    {
        $codeIgniterVersion = $this->determineCodeIgniterVersion();

        if ($codeIgniterVersion === CIVersion::UNKNOWN) {
            $this->logService->error("Could not determine CodeIgniter version or unsupported version.");
            return false;
        }

        $this->logService->info("CodeIgniter version detected: {$codeIgniterVersion->value}");

        $this->currentConverter = $this->resolveConverterFor($codeIgniterVersion);

        if (!$this->currentConverter) {
            $this->logService->error("No migration converter found for CodeIgniter version: {$codeIgniterVersion->value}");
            return false;
        }

        // Call the specific migration handler based on the detected version
        $this->handleMigration($codeIgniterVersion);

        return true;
    }

    /**
     * Determines the CodeIgniter version.
     *
     * @return CIVersion The detected CodeIgniter version.
     */
    protected function determineCodeIgniterVersion(): CIVersion
    {
        $codeIgniterVersion = $this->getCodeIgniterVersion(); // Assuming this method exists from HasDirectories

        if ($codeIgniterVersion === CIVersion::UNKNOWN) {
            $codeIgniterVersion = $this->readCodeIgniterVersionFromFile();
            $this->setCodeIgniterVersion($codeIgniterVersion); // Assuming this method exists from HasDirectories
        }
        return $codeIgniterVersion;
    }

    /**
     * Reads the CodeIgniter version from the project files.
     *
     * @return CIVersion The detected CodeIgniter version.
     */
    protected function readCodeIgniterVersionFromFile(): CIVersion
    {
        $corePath = $this->getCICoreDirectory();
        $compatPath = $this->getCICompatDirectory();
        $systemPath = $this->getCISystemDirectory();

        // CI2: No compat directory, core directory exists, and CodeIgniter.php contains 'CI_VERSION'
        if (is_dir($corePath) && !is_dir($compatPath)) {
            $fileContent = @file_get_contents($corePath . DIRECTORY_SEPARATOR . 'CodeIgniter.php');
            if ($fileContent !== false && strpos($fileContent, 'CI_VERSION') !== false) {
                return CIVersion::CI2;
            }
        }

        // CI3: compat directory exists within the application directory
        if (is_dir($corePath) && is_dir($compatPath)) {
            return CIVersion::CI3;
        }

        // CI4: system directory contains a bootstrap.php file and no core directory (newer structure)
        $bootstrapPath = $systemPath . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (file_exists($bootstrapPath) && !is_dir($corePath)) {
            return CIVersion::CI4;
        }

        return CIVersion::UNKNOWN;
    }

    /**
     * Handles the migration based on the detected CodeIgniter version.
     *
     * @param CIVersion $version The CodeIgniter version to migrate.
     * @return void
     */
    protected function handleMigration(CIVersion $version): void
    {
        switch ($version) {
            case CIVersion::CI2:
                $this->logService->info("Initiating CodeIgniter 2 migration...");
                // Delegating to the converter
                $this->currentConverter->convert();
                // TODO: Implement the logic for handling CodeIgniter 2 migration.
                // This should include:
                // 1. Parsing and converting configuration files to Laravel's format.
                // 2. Migrating database schema and data if applicable.
                // 3. Adjusting file structures and namespaces to align with Laravel conventions.
                // 4. Handling any specific features or quirks of CodeIgniter 2.
                // 5. Testing the migrated application to ensure functionality.

                // Note: Refer to the CodeIgniter 2 documentation for details on its structure and features.
                break;
            case CIVersion::CI3:
                $this->logService->info("Initiating CodeIgniter 3 migration...");
                $this->prepareLaravelProject();
                // Delegating to the converter
                $this->currentConverter->convert();

                // TODO: Implement the remaining logic for handling CodeIgniter 3 migration.
                // This should include:
                // 1. Parsing and converting configuration files to Laravel's format. (Partially done by convertCI3ConfigToLaravel)
                // 2. Migrating database schema and data if applicable.
                // 3. Adjusting file structures and namespaces to align with Laravel conventions.
                // 4. Handling any specific features or quirks of CodeIgniter 3.
                // 5. Testing the migrated application to ensure functionality.

                // Note: Refer to the CodeIgniter 3 documentation for details on its structure and features.
                break;
            case CIVersion::CI4:
                $this->logService->info("Initiating CodeIgniter 4 migration...");
                // Delegating to the converter
                $this->currentConverter->convert();
                // Logic for handling CodeIgniter 4 migration
                // This might be simpler since CI4 is more aligned with modern PHP practices.
                break;
            default:
                // This case should ideally not be reached if determineCodeIgniterVersion works as expected
                $this->logService->error("Unsupported CodeIgniter version encountered: {$version->value}");
                break;
        }
    }

    /**
     * Prepares the Laravel project by prompting for details and creating it.
     * This method is specifically called for CI3 migration and can be generalized if needed.
     *
     * @return void
     */
    protected function prepareLaravelProject(): void
    {
        // For CI3, we assume project setup happens before conversion
        $projectName = $this->promptService->promptForProjectName();
        $laravelVersion = $this->promptService->promptForLaravelVersion();
        $this->logService->info("Selected Laravel Version: {$laravelVersion}");
        $installSail = $this->promptService->promptForSailInstall();

        $this->laravelProjectSetupService->setLaravelProjectName($projectName);
        $this->laravelProjectSetupService->createAndSetupProject(
            $projectName,
            $laravelVersion,
            $installSail
        );
    }
}
