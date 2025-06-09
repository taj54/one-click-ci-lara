<?php

namespace App\Contracts;

use App\Enums\CIVersion;

interface CIAnalyzerInterface
{
    /**
     * Determine whether this analyzer supports a given CodeIgniter version.
     *
     * @param CIVersion $version
     * @return bool
     */
    public function supports(CIVersion $version): bool;

    /**
     * Analyze the CodeIgniter project and return structured metadata.
     *
     * Typical return data may include:
     * - controllers
     * - models
     * - routes
     * - libraries
     * - helpers
     *
     * @return array
     */
    public function analyze(): array;
}
