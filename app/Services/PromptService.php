<?php

namespace App\Services;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class PromptService
{
    public function promptForProjectName()
    {
        return text(
            label: 'Enter the name of the Laravel project',
            default: 'laravel_project',
            placeholder: 'My Laravel Project',
            required: true
        );
    }

    public function promptForLaravelVersion()
    {
        return select('Select Laravel Version', [
            '8.x' => 'Laravel 8.x',
            '9.x' => 'Laravel 9.x',
            '10.x' => 'Laravel 10.x',
            '11.x' => 'Laravel 11.x',
        ], default: '8.x');
    }

    public function promptForSailInstall()
    {
        return confirm(
            label: 'Do you want to install Laravel Sail?',
            default: true
        );
    }

    // Add more prompts as needed
}