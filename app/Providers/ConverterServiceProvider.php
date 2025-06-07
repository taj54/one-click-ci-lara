<?php

namespace App\Providers;

use App\Contracts\CodeIgniterConverterInterface;
use App\Services\CodeIgniterMigrationService;
use App\Services\Converters\CI3ConverterService;
use App\Services\FileHandlerService;
use App\Services\LaravelProjectSetupService;
use App\Services\LogService;
use App\Services\PromptService;
use App\Services\Utility\PhpFileParser;
use Illuminate\Support\ServiceProvider;

class ConverterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->tag([
            // CI2ConverterService::class,
            CI3ConverterService::class,
            // CI4ConverterService::class,
        ], CodeIgniterConverterInterface::class);

        $this->app->bind(CodeIgniterMigrationService::class, function ($app) {
            return new CodeIgniterMigrationService(
                // $app->make(CI3ConverterService::class),
                $app->make(PromptService::class),
                $app->make(LogService::class),
                $app->make(LaravelProjectSetupService::class),
                $app->make(FileHandlerService::class),
                $app->tagged(CodeIgniterConverterInterface::class) // <== inject tagged services here
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
