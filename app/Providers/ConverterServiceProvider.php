<?php

namespace App\Providers;

use App\Contracts\CIAnalyzerInterface;
use App\Contracts\CIConverterInterface;
use App\Contracts\CIMigrationCoordinatorInterface;
use App\Services\Analyzers\CI3AnalyzerService;
use App\Services\Analyzers\CIAnalyzerService;
use App\Services\CodeIgniterMigrationService;
use App\Services\Converters\CI3ConverterService;
use App\Services\Coordinators\CI3MigrationCoordinatorService;
use App\Services\Coordinators\CIMigrationCoordinatorService;
use App\Services\FileHandlerService;
use App\Services\LaravelProjectSetupService;
use App\Services\LogService;
use App\Services\PromptService;
use Illuminate\Support\ServiceProvider;

class ConverterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {


        $this->app->tag([
            // CIMigrationCoordinatorService::class,
            CI3MigrationCoordinatorService::class,
        ], CIMigrationCoordinatorInterface::class);
        $this->app->tag([
            // CI2ConverterService::class,
            CI3ConverterService::class,
            // CI4ConverterService::class,
        ], CIConverterInterface::class);
        $this->app->tag([
            CI3AnalyzerService::class
        ], CIAnalyzerInterface::class);

        $this->app->bind(CI3MigrationCoordinatorService::class, function ($app) {
            return new CI3MigrationCoordinatorService(
                $app->tagged(CIAnalyzerInterface::class),
                $app->tagged(CIConverterInterface::class),
            );
        });


        $this->app->bind(CodeIgniterMigrationService::class, function ($app) {
            return new CodeIgniterMigrationService(
                $app->make(PromptService::class),
                $app->make(LogService::class),
                $app->make(LaravelProjectSetupService::class),
                $app->make(FileHandlerService::class),
                $app->make(CIMigrationCoordinatorService::class)
            );
        });

        $this->app->bind(CIMigrationCoordinatorService::class, function ($app) {
            return new CIMigrationCoordinatorService(
                $app->tagged(CIMigrationCoordinatorInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
