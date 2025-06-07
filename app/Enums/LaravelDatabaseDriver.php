<?php

namespace App\Enums;

/**
 * Enum representing supported Laravel database drivers with utility methods
 * to bridge compatibility with CodeIgniter database configuration.
 */
enum LaravelDatabaseDriver: string
{
    case MYSQL  = 'mysql';
    case SQLITE = 'sqlite';
    case PGSQL  = 'pgsql';
    case SQLSRV = 'sqlsrv';
    // case ODBC   = 'odbc'; // Uncomment to support ODBC explicitly

    /**
     * Mapping of CodeIgniter driver names to Laravel drivers.
     */
    private const CI_TO_LARAVEL_MAP = [
        'mysqli'  => 'mysql',
        'mysql'   => 'mysql',
        'sqlite3' => 'sqlite',
        'sqlite'  => 'sqlite',
        'postgre' => 'pgsql',
        'pgsql'   => 'pgsql',
        'sqlsrv'  => 'sqlsrv',
        'odbc'    => 'odbc', // Use only if case ODBC is enabled
    ];

    /**
     * Gets the LaravelDatabaseDriver enum from a CodeIgniter driver string.
     * Defaults to MYSQL if no valid match is found.
     *
     * @param string $ciDriver CodeIgniter database driver name.
     * @return self
     */
    public static function fromCIDriver(string $ciDriver): self
    {
        $driver = strtolower($ciDriver);
        $laravelDriver = self::CI_TO_LARAVEL_MAP[$driver] ?? self::MYSQL->value;

        return self::tryFrom($laravelDriver) ?? self::MYSQL;
    }

    /**
     * Gets the default port number for the current driver.
     *
     * @return int
     */
    public function defaultPort(): int
    {
        return match ($this) {
            self::MYSQL  => 3306,
            self::PGSQL  => 5432,
            self::SQLSRV => 1433,
            default      => 3306,
        };
    }

    /**
     * Returns Laravel-specific extra config based on CodeIgniter config values.
     *
     * @param array $ciDatabaseValues CodeIgniter config array.
     * @return array
     */
    public function extraConfig(array $ciDatabaseValues): array
    {
        return match ($this) {
            self::MYSQL => $this->mysqlExtraConfig($ciDatabaseValues),
            self::SQLITE => $this->sqliteExtraConfig($ciDatabaseValues),
            self::PGSQL => $this->pgsqlExtraConfig($ciDatabaseValues),
            self::SQLSRV => $this->sqlsrvExtraConfig($ciDatabaseValues),
            // self::ODBC => $this->odbcExtraConfig($ciDatabaseValues),
            default =>  [
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

    private function mysqlExtraConfig(array $ci): array
    {
        return [
            'driver'          => self::MYSQL->value,
            'charset'         => $ci['char_set'] ?? 'utf8mb4',
            'collation'       => $ci['dbcollat'] ?? 'utf8mb4_unicode_ci',
            'prefix'          => $ci['dbprefix'] ?? '',
            'prefix_indexes'  => true,
            'strict'          => (bool)($ci['stricton'] ?? true),
            'engine'          => 'InnoDB',
        ];
    }

    private function sqliteExtraConfig(array $ci): array
    {
        return [
            'driver'                  =>  self::SQLITE->value,
            'prefix'                  => $ci['dbprefix'] ?? '',
            'foreign_key_constraints' => true,
        ];
    }

    private function pgsqlExtraConfig(array $ci): array
    {
        return [
            'driver'         => self::PGSQL->value,
            'charset'        => $ci['char_set'] ?? 'utf8',
            'prefix'         => $ci['dbprefix'] ?? '',
            'prefix_indexes' => true,
            'schema'         => 'public',
            'sslmode'        => 'prefer',
        ];
    }

    private function sqlsrvExtraConfig(array $ci): array
    {
        return [
            'driver'         => self::SQLSRV->value,
            'charset'        => $ci['char_set'] ?? 'utf8',
            'prefix'         => $ci['dbprefix'] ?? '',
            'prefix_indexes' => true,
        ];
    }

    // private function odbcExtraConfig(array $ci): array
    // {
    //     return [
    //         // ODBC-specific config goes here
    //     ];
    // }
}
