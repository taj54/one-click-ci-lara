<?php

namespace App\Services\Coordinators;

use App\Contracts\CIMigrationCoordinatorInterface;
use App\Enums\CIVersion;
use RuntimeException;

class CIMigrationCoordinatorService implements CIMigrationCoordinatorInterface
{
    private $currentCoordinator;
    /**
     * @param CIMigrationCoordinatorInterface[] $coordinators
     */
    public function __construct(
        private iterable $coordinators
    ) {}

    /**
     * Determine if any of the coordinators support the given version.
     */
    public function supports(CIVersion $version): bool
    {
        foreach ($this->coordinators as $coordinator) {
            if ($coordinator->supports($version)) {
                $this->currentCoordinator = $coordinator;
                return true;
            }
        }
        return false;
    }

    /**
     * Execute migration by delegating to the correct version-specific coordinator.
     *
     * @param CIVersion $version
     * @return array
     * @throws RuntimeException if no suitable coordinator found
     */
    public function executeMigration(): array
    {
        if ($this->currentCoordinator) {
            return $this->currentCoordinator->executeMigration();
        }

        // throw new RuntimeException("No migration coordinator found for CodeIgniter version: {$version->name}");
        return [];
    }
}
