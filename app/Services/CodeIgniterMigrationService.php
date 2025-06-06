<?php

namespace App\Services;

use App\Traits\HasDirectories;

class CodeIgniterMigrationService
{
    use HasDirectories;

    protected $logService;
    // protected $codeIgnatorVersion;
    protected $ci3ConverterService;
    protected $promptService;
    protected $laravelProjectSetupService;

    public function __construct(
        CI3ConverterService $ci3ConverterService,
        PromptService $promptService,
        LogService $logService,
        LaravelProjectSetupService $laravelProjectSetupService // Add this
    ) {
        $this->ci3ConverterService = $ci3ConverterService;
        $this->promptService = $promptService;
        $this->logService = $logService;
        $this->laravelProjectSetupService = $laravelProjectSetupService;
    }



    public function migrate()
    {
    
        $codeIgnatorVersion = $this->getCodeIgniterVersion();
        if (empty($codeIgnatorVersion)) {
            $this->getCodeIgniterVersionFromFile();
            $codeIgnatorVersion = $this->getCodeIgniterVersion();
        }
        $this->logService->info("CodeIgniter version is {$codeIgnatorVersion}");

        // Now branch your migration logic based on the detected version
        switch ($codeIgnatorVersion) {
            case 'CI2':
                $this->handleCI2Migration();
                break;
            case 'CI3':
                $this->handleCI3Migration();
                break;
            case 'CI4':
                $this->handleCI4Migration();
                break;
            default:
                $this->logService->error("Could not determine CodeIgniter version or unsupported version: {$codeIgnatorVersion}");
                return false;
        }

        return true;
    }

    /**
     * Additional methods for migration logic can be added here.
     * For example, methods to handle specific CodeIgniter features,
     * database migrations, or configuration adjustments.
     */
    public function getCodeIgniterVersionFromFile()
    {
        $corePath = $this->getCICoreDirectory();
        $compatPath = $this->getCICompatDirectory();


        // CI2: No compat directory, core directory exists, and CodeIgniter.php contains 'CI_VERSION'
        if (is_dir($corePath) && !is_dir($compatPath)) {
            $fileContent = @file_get_contents($corePath . DIRECTORY_SEPARATOR . 'CodeIgniter.php');
            if ($fileContent !== false && strpos($fileContent, 'CI_VERSION') !== false) {
                $this->logService->info('CI2');
                $this->setCodeIgniterVersion('CI2');
                return 'CI2';
            }
        }

        // CI3: compat directory exists
        if (is_dir($corePath) && is_dir($compatPath)) {
            $this->logService->info('CI3');
            $this->setCodeIgniterVersion('CI3');
            return 'CI3';
        }

        // CI4: system directory contains a bootstrap.php file and no core directory
        $bootstrapPath = $this->getCISystemDirectory() . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (file_exists($bootstrapPath) && !is_dir($corePath)) {
            $this->logService->info('CI4');
            $this->setCodeIgniterVersion('CI4');
            return 'CI4';
        }

        $this->setCodeIgniterVersion('Unknown'); // Set to unknown if not detected
        return null;
    }

    public function handleCI2Migration()
    {
        $this->logService->info("Handling CodeIgniter 2 migration (logic to be implemented)...");
        // TODO: Implement the logic for handling CodeIgniter 2 migration.
        // This should include:
        // 1. Parsing and converting configuration files to Laravel's format.
        // 2. Migrating database schema and data if applicable.
        // 3. Adjusting file structures and namespaces to align with Laravel conventions.
        // 4. Handling any specific features or quirks of CodeIgniter 2.
        // 5. Testing the migrated application to ensure functionality.

        // Note: Refer to the CodeIgniter 2 documentation for details on its structure and features.
    }

    public function handleCI3Migration()
    {
        $this->convertCI3ConfigToLaravel();


        // TODO: Implement the remaining logic for handling CodeIgniter 3 migration.
        // This should include:
        // 1. Parsing and converting configuration files to Laravel's format. (Partially done by convertCI3ConfigToLaravel)
        // 2. Migrating database schema and data if applicable.
        // 3. Adjusting file structures and namespaces to align with Laravel conventions.
        // 4. Handling any specific features or quirks of CodeIgniter 3.
        // 5. Testing the migrated application to ensure functionality.

        // Note: Refer to the CodeIgniter 3 documentation for details on its structure and features.
    }

    public function handleCI4Migration()
    {
        $this->logService->info("Handling CodeIgniter 4 migration (logic to be implemented)...");
        // Logic for handling CodeIgniter 4 migration
        // This might be simpler since CI4 is more aligned with modern PHP practices.
    }

    /**
     * Parse CodeIgniter 3 config.php and convert to Laravel config/app.php format.
     *
     * @return void
     */
    public function convertCI3ConfigToLaravel()
    {
        if (!$this->checkCI3ConfigExists($this->getCIConfigFile())) {
            $this->logService->error("CodeIgniter config.php not found at:".$this->getCIConfigFile());
            return;
        }

        $projectName = $this->promptService->promptForProjectName();
        $laravelVersion = $this->promptService->promptForLaravelVersion();
        $this->logService->info("Selected Laravel Version: $laravelVersion");
        $installSail = $this->promptService->promptForSailInstall();

        $this->laravelProjectSetupService->setLaravelProjectName($projectName);


        $this->laravelProjectSetupService->createAndSetupProject(
            $projectName,
            $laravelVersion,
            $installSail,
        );

        // $this->ci3ConverterService->setLaravelProjectDirectory($projectName);
        $laravelConfigPath = $this->ci3ConverterService->getLaravelProjectDirectory();
        $this->ci3ConverterService->convert();
    }



    protected function checkCI3ConfigExists($ciConfigPath)
    {
        if (!file_exists($ciConfigPath)) {
            return false;
        }
        return true;
    }

}
