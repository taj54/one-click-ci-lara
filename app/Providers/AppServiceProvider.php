<?php

namespace App\Providers;

use App\Services\LogService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
