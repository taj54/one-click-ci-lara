<?php

namespace App\Support;

use App\Enums\LaravelDatabaseDriver;

class DatabaseConnectionConfig
{
    public function __construct(
        public LaravelDatabaseDriver $driver,
        public array $ciConfig = [],
        public ?int $customPort = null
    ) {}

    public function resolvedPort(): int
    {
        return $this->customPort ?? $this->driver->defaultPort();
    }

    public function toLaravelConfig(): array
    {
        return array_merge(
            $this->driverSpecificConfig(),
            // ['port' => $this->resolvedPort()]
        );
    }

    private function driverSpecificConfig(): array
    {
        return match ($this->driver) {
            LaravelDatabaseDriver::MYSQL => [
                'driver'         => 'mysql',
                'charset'        => $this->ciConfig['char_set'] ?? 'utf8mb4',
                'collation'      => $this->ciConfig['dbcollat'] ?? 'utf8mb4_unicode_ci',
                'prefix'         => $this->ciConfig['dbprefix'] ?? '',
                'prefix_indexes' => true,
                'strict'         => (bool)($this->ciConfig['stricton'] ?? true),
                'engine'         => 'InnoDB',
            ],
            LaravelDatabaseDriver::SQLITE => [
                'driver'                  => 'sqlite',
                'prefix'                  => $this->ciConfig['dbprefix'] ?? '',
                'foreign_key_constraints' => true,
            ],
            LaravelDatabaseDriver::PGSQL => [
                'driver'         => 'pgsql',
                'charset'        => $this->ciConfig['char_set'] ?? 'utf8',
                'prefix'         => $this->ciConfig['dbprefix'] ?? '',
                'prefix_indexes' => true,
                'schema'         => 'public',
                'sslmode'        => 'prefer',
            ],
            LaravelDatabaseDriver::SQLSRV => [
                'driver'         => 'sqlsrv',
                'charset'        => $this->ciConfig['char_set'] ?? 'utf8',
                'prefix'         => $this->ciConfig['dbprefix'] ?? '',
                'prefix_indexes' => true,
            ],
            default => [
                'driver' => 'mysql',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => 'default_',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => 'InnoDB',
            ],
        };
    }
}
