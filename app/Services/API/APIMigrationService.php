<?php

namespace App\Services\API;

use App\Services\Abstracts\BaseCIMigrationService;

class APIMigrationService extends BaseCIMigrationService
{
    protected function setupLaravelProject(): void
    {
        $projectName = $this->injectedProjectName;
        $laravelVersion = $this->injectedLaravelVersion;
        $installSail = $this->injectedInstallSail;

        if (!$projectName || !$laravelVersion || $installSail === null) {
            throw new \RuntimeException("Missing required project input for API migration.");
        }

        $this->logService->info("Selected Laravel Version: {$laravelVersion}");

        $this->laravelProjectSetupService->setLaravelProjectName($projectName);
        $this->laravelProjectSetupService->createAndSetupProject($projectName, $laravelVersion, $installSail);
    }
}
