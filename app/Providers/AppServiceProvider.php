<?php

namespace App\Providers;

use App\Services\LogService;
use App\Services\StatusBarService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Application as Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogService::class, function ($app) {
            // Always use the main console output for CLI
            return new LogService(new ConsoleOutput());
        });
        $this->app->bind(StatusBarService::class, function ($app) {
            // Always use the main console output for CLI
            return new StatusBarService(new ConsoleOutput());
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
