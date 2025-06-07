<?php

namespace App\Providers;

use App\Services\LogService;
use App\Services\StatusBarService;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogService::class, function ($app) {
            return new LogService(new ConsoleOutput());
        });
        $this->app->bind(StatusBarService::class, function ($app) {
            return new StatusBarService(new ConsoleOutput());
        });

        // $this->app->tag([
        //     CIConfigNodeProcessor::class,
        //     CIDatabaseNodeProcessor::class
        // ], NodeProcessorInterface::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
