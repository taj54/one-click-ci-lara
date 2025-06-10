<?php

namespace App\Services\CLI;

use App\Services\Abstracts\BaseCIMigrationService;
use App\Services\PromptService;

class CLIMigrationService extends BaseCIMigrationService
{
    public function __construct(
        private PromptService $promptService,
        ...$dependencies // include other dependencies and pass to parent
    ) {
        parent::__construct(...$dependencies);
    }

    protected function setupLaravelProject(): void
    {
        $projectName = $this->injectedProjectName ?? $this->promptService->promptForProjectName();
        $laravelVersion = $this->injectedLaravelVersion ?? $this->promptService->promptForLaravelVersion();
        $installSail = $this->injectedInstallSail ?? $this->promptService->promptForSailInstall();

        $this->logService->info("Selected Laravel Version: {$laravelVersion}");

        $this->laravelProjectSetupService->setLaravelProjectName($projectName);
        $this->laravelProjectSetupService->createAndSetupProject($projectName, $laravelVersion, $installSail);
    }
}
