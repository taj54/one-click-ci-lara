<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;

class LaravelProjectSetupService
{
    protected $laravelProjectService;
    protected $logService;

    public function __construct(LaravelProjectService $laravelProjectService, LogService $logService)
    {
        $this->laravelProjectService = $laravelProjectService;
        $this->logService = $logService;
    }

    /**
     * Create a new Laravel project and set up its environment.
     */
    public function createAndSetupProject($outputDirectory, $projectName, $laravelVersion, $installSail)
    {
        $outputDir = rtrim($outputDirectory, DIRECTORY_SEPARATOR);
        $projectPath = $outputDir . DIRECTORY_SEPARATOR . $projectName;
        $envPath = $projectPath . DIRECTORY_SEPARATOR . '.env';

        $this->laravelProjectService->createLaravelProject($outputDirectory, $projectName, $laravelVersion, $installSail);


        // Generate app key
        $this->generateAppKey($projectPath, $envPath);

        return [$projectPath, $envPath];
    }


    public function generateAppKey($projectPath, $envPath)
    {

        if (getcwd() !== $projectPath && !@chdir($projectPath)) {
            $this->logService->error("Could not change directory to {$projectPath}. Aborting key generation.");
            return false;
        }
        // Generate key string:
        $key = $this->generateRandomKey();

        // Read .env
        $envContent = file_get_contents($envPath);

        // Replace or add APP_KEY line
        if (preg_match('/^APP_KEY=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $envContent);
        } else {
            $envContent .= "\nAPP_KEY={$key}\n";
        }

        file_put_contents($envPath, $envContent);


        return false;
        // Uncomment the following lines if you want to run the artisan command directly having some trouble with exec
        // $artisanPath = "{$projectPath}\\artisan";

        // if (!file_exists($artisanPath)) {
        //     $this->logService->error("Artisan file not found at: {$artisanPath}");
        //     return;
        // }

        // $phpPath = PHP_BINARY;
        // $command = "$phpPath \"{$artisanPath}\" key:generate --ansi";
        // $this->logService->info("Running command: {$command}");

        // $output = [];
        // $returnVar = 0;
        // exec($command, $output, $returnVar);

        // if ($returnVar !== 0) {
        //     $this->logService->error("Failed to run artisan key:generate:\n" . implode("\n", $output));
        // } else {
        //     $this->logService->info("Key generated successfully:\n" . implode("\n", $output));
        // }
    }
    protected function generateRandomKey()
    {
        $cipher = 'AES-256-CBC';

        return 'base64:' . base64_encode(
            Encrypter::generateKey($cipher)
        );
    }
}
