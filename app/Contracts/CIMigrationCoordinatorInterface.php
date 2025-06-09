<?php

namespace App\Contracts;

use App\Enums\CIVersion;

interface CIMigrationCoordinatorInterface
{
    /**
     * Determines whether this coordinator supports the given CI version.
     *
     * @param CIVersion $version
     * @return bool
     */
    public function supports(CIVersion $version): bool;

    /**
     * Executes the migration process: analysis and conversion.
     *
     * @param CIVersion $version
     * @return array Structured result containing analysis and conversion info
     */
    public function executeMigration(): array;
}
