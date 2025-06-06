<?php

namespace App\Services;

use App\Traits\HasDirectories;
use App\Traits\HasStatusBar;
use Illuminate\Encryption\Encrypter;

class LaravelProjectSetupService
{
    use HasDirectories, HasStatusBar;
    protected $laravelProjectService;
    protected $logService;
    private $laravelProjectDirectory;
    private $laravelENVFile;

    public function __construct(LaravelProjectService $laravelProjectService, LogService $logService)
    {
        $this->laravelProjectService = $laravelProjectService;
        $this->logService = $logService;
    }

    /**
     * Create a new Laravel project and set up its environment.
     */
    public function createAndSetupProject($projectName, $laravelVersion, $installSail)
    {
        $this->laravelProjectDirectory = $this->getLaravelProjectDirectory();
        $this->laravelENVFile = $this->getLaravelENVFile();

        $this->laravelProjectService->createLaravelProject(
            $projectName,
            $laravelVersion,
            $installSail,
        );

        $this->generateAppKey();

        return true;
    }

    public function generateAppKey()
    {

        if (getcwd() !== $this->laravelProjectDirectory && !@chdir($this->laravelProjectDirectory)) {
            $this->logService->error("Could not change directory to {$this->laravelProjectDirectory}. Aborting key generation.");
            return false;
        }
        // Generate key string:
        $key = $this->generateRandomKey();

        // Read .env
        $envContent = file_get_contents($this->laravelENVFile);

        // Replace or add APP_KEY line
        if (preg_match('/^APP_KEY=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $envContent);
        } else {
            $envContent .= "\nAPP_KEY={$key}\n";
        }

        file_put_contents($this->laravelENVFile, $envContent);


        return false;
        // Uncomment the following lines if you want to run the artisan command directly having some trouble with exec
        // $artisanPath = "{$this->laravelProjectDirectory}\\artisan";

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
