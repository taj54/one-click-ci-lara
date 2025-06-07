<?php

namespace App\Services\Converters;

use App\Contracts\CodeIgniterConverterInterface;
use App\Enums\CIVersion;
use App\Services\FileHandlerService;
use App\Services\LogService;
use App\Services\Parsers\NodeProcessors\CIConfigNodeProcessor;
use App\Services\Parsers\NodeProcessors\CIDatabaseNodeProcessor;
use App\Services\Utility\PhpFileParser;
use App\Traits\HasDirectories;
use RuntimeException;

class CI3ConverterService implements CodeIgniterConverterInterface
{
    use HasDirectories;

    private LogService $logService;
    private PhpFileParser $phpFileParser;
    private FileHandlerService $fileHandlerService;
    private array $ciConfigValues = [];
    private array $ciDatabaseValues = [];

    public function __construct(LogService $logService, PhpFileParser $phpFileParser, FileHandlerService $fileHandlerService)
    {
        $this->logService = $logService;
        $this->phpFileParser = $phpFileParser;
        $this->fileHandlerService = $fileHandlerService;
    }
    public function supports(CIVersion $version): bool
    {
        return $version === CIVersion::CI3;
    }
    public function convert(): bool
    {
        $configParsed = $this->parseCIConfig();
        $databaseParsed = $this->parseCIDatabase();

        if (!$configParsed && !$databaseParsed) {
            $this->logService->error("Failed to parse both CI config and database files. No .env updates will be applied.");
            return false;
        }

        $envPath = $this->getLaravelENVFile();
        $dbPath = $this->getLaravelDataBaseFile();

        if (!$this->fileHandlerService->validateFileExists($envPath) || !$this->fileHandlerService->validateFileExists($dbPath)) {
            return false;
        }

        $envContent = file_get_contents($envPath);
        $dbContent = file_get_contents($dbPath);

        if ($envContent === false || $dbContent === false) {
            return $this->fail("Failed to read one or more config files.");
        }

        $envUpdates = $this->buildEnvUpdates();
        $extraDbConfig = $this->mapCIextraDBConfig();
        $driver = $extraDbConfig['driver'] ?? null;

        if (!$driver) {
            throw new \InvalidArgumentException("Missing 'driver' key.");
        }

        unset($extraDbConfig['driver']);

        if (!empty($extraDbConfig)) {
            if (!$this->updateDatabaseConfig($dbPath, $driver, $extraDbConfig)) {
                return false;
            }
        }

        $envContent = $this->applyEnvUpdates($envContent, $envUpdates);

        if (file_put_contents($envPath, $envContent) === false) {
            return $this->fail("Failed to write updated content to .env file at: {$envPath}");
        }

        $this->logService->info(".env file updated successfully.");
        return true;
    }

    private function parseCIConfig(): bool
    {
        try {
            $nodeProcessor = $this->phpFileParser->parse(
                $this->getCIConfigFile(),
                new CIConfigNodeProcessor()
            );
            $this->ciConfigValues = $nodeProcessor->getResults();
            return true;
        } catch (RuntimeException $e) {
            return $this->fail("Error parsing CI config.php: " . $e->getMessage());
        }
    }

    private function parseCIDatabase(): bool
    {
        try {
            $nodeProcessor = $this->phpFileParser->parse(
                $this->getCIDataBaseFile(),
                new CIDatabaseNodeProcessor()
            );
            $this->ciDatabaseValues = $nodeProcessor->getResults();

            if (empty($this->ciDatabaseValues)) {
                $this->logService->comment("No \$db['default'] config found. Skipping DB conversion.");
                return false;
            }

            return true;
        } catch (RuntimeException $e) {
            return $this->fail("Error parsing CI database.php: " . $e->getMessage());
        }
    }

    private function buildEnvUpdates(): array
    {
        $driverMap = [
            'mysql' => 'mysql',
            'mysqli' => 'mysql',
            'postgre' => 'pgsql',
            'sqlite' => 'sqlite',
            'sqlite3' => 'sqlite',
            'sqlsrv' => 'sqlsrv',
            'odbc' => 'odbc',
        ];

        $rawDriver = $this->ciDatabaseValues['dbdriver'] ?? 'mysql';
        $driver = $driverMap[$rawDriver] ?? 'mysql';
        $this->ciDatabaseValues['dbdriver'] = $driver;

        $host = preg_replace('/^https?:\/\//', '', $this->ciDatabaseValues['hostname'] ?? '127.0.0.1');
        [$dbHost, $dbPort] = array_pad(explode(':', $host), 2, $this->defaultPortForDriver($driver));

        $appUrl = preg_replace('/^https?:\/\//', '', $this->ciConfigValues['base_url'] ?? 'http://localhost');

        return [
            'APP_NAME' => $this->ciConfigValues['site_title']
                ?? $this->ciConfigValues['site_name']
                ?? 'Laravel Migration App',
            'APP_ENV' => 'local',
            'APP_DEBUG' => !empty($this->ciConfigValues['debug']) ? 'true' : 'false',
            'APP_URL' => $appUrl,
            'DB_CONNECTION' => $driver,
            'DB_HOST' => $dbHost,
            'DB_PORT' => $dbPort,
            'DB_DATABASE' => $this->ciDatabaseValues['database'] ?? '',
            'DB_USERNAME' => $this->ciDatabaseValues['username'] ?? '',
            'DB_PASSWORD' => $this->ciDatabaseValues['password'] ?? '',
        ];
    }

    private function defaultPortForDriver(string $driver): int
    {
        return match ($driver) {
            'mysql' => 3306,
            'pgsql' => 5432,
            'sqlsrv' => 1433,
            default => 3306,
        };
    }

    private function applyEnvUpdates(string $envContent, array $updates): string
    {
        foreach ($updates as $key => $value) {
            $envContent = $this->setEnvValue($envContent, $key, $value);
        }
        return $envContent;
    }

    private function updateDatabaseConfig(string $filePath, string $driver, array $updates): bool
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            $this->logService->error("Failed to read database config file.");
            return false;
        }

        $pattern = "/(['\"]{$driver}['\"]\s*=>\s*\[)(.*?)(\n\s*],)/ms";

        if (!preg_match($pattern, $fileContent, $matches, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("Could not find driver '{$driver}' in the config file.");
        }

        $blockStartIndex = $matches[0][1];
        $fullBlockLength = strlen($matches[0][0]);

        $blockHeader = $matches[1][0]; // 'mysql' => [
        $blockBody   = $matches[2][0]; // inside block
        $blockFooter = $matches[3][0]; // closing ]

        foreach ($updates as $key => $value) {
            $valueString = var_export($value, true);

            // Match lines like: 'charset' => 'utf8mb4', possibly with spaces or comments
            $keyPattern = "/(['\"]{$key}['\"]\s*=>\s*)([^,\n]+)(,?)(\s*\/\/[^\n]*)?/";

            if (preg_match($keyPattern, $blockBody)) {
                $blockBody = preg_replace(
                    $keyPattern,
                    "'{$key}' => {$valueString},",
                    $blockBody
                );
            } else {
                $blockBody .= "\n        '{$key}' => {$valueString},";
            }
        }

        $newBlock = $blockHeader . $blockBody . $blockFooter;
        $updatedFile = substr_replace($fileContent, $newBlock, $blockStartIndex, $fullBlockLength);

        if (file_put_contents($filePath, $updatedFile) === false) {
            $this->logService->error("Failed to write updated database config to {$filePath}");
            return false;
        }

        $this->logService->info("🔁 Updated driver '{$driver}' with keys: " . implode(', ', array_keys($updates)) . "\n");
        return true;
    }




    private function fail(string $message): bool
    {
        $this->logService->error($message);
        return false;
    }

    protected function setEnvValue(string $envContents, string $key, string|int|bool|null $value): string
    {
        $value = (string) $value;
        if (preg_match('/\s|"|\'/', $value)) {
            $value = '"' . str_replace('"', '\"', $value) . '"';
        }

        $pattern = "/^{$key}=.*/m";
        $line = "{$key}={$value}";

        if (preg_match($pattern, $envContents)) {
            return preg_replace($pattern, $line, $envContents);
        }

        $keyPrefix = explode('_', $key)[0];
        if (preg_match_all("/^{$keyPrefix}_.*\n?/m", $envContents, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $pos = $lastMatch[1] + strlen($lastMatch[0]);
            return substr_replace($envContents, $line . PHP_EOL, $pos, 0);
        }

        return rtrim($envContents, "\r\n") . PHP_EOL . $line . PHP_EOL;
    }

    private function mapCIextraDBConfig(): array
    {
        $ciDriver = strtolower($this->ciDatabaseValues['dbdriver'] ?? 'mysql');

        $driverMap = [
            'mysqli'  => 'mysql',
            'mysql'   => 'mysql',
            'sqlite3' => 'sqlite',
            'sqlite'  => 'sqlite',
            'postgre' => 'pgsql',
            'pgsql'   => 'pgsql',
            'sqlsrv'  => 'sqlsrv',
            'odbc'    => 'odbc',
        ];

        $driver = $driverMap[$ciDriver];
        $this->ciDatabaseValues['dbdriver'] = $driver;
        return match ($driver) {
            'mysql' => [
                'driver' => $driver,
                'charset' => $this->ciDatabaseValues['char_set'] ?? 'utf8mb4',
                'collation' => $this->ciDatabaseValues['dbcollat'] ?? 'utf8mb4_unicode_ci',
                'prefix' => $this->ciDatabaseValues['dbprefix'] ?? '_Dasd',
                'prefix_indexes' => false,
                'strict' => (bool)($this->ciDatabaseValues['stricton'] ?? true),
                'engine' => 'InnoDB',
            ],
            'sqlite' => [
                'driver' => $driver,
                'prefix' => $this->ciDatabaseValues['dbprefix'] ?? '',
                'foreign_key_constraints' => true,
            ],
            'pgsql' => [
                'driver' => $driver,
                'charset' => $this->ciDatabaseValues['char_set'] ?? 'utf8',
                'prefix' => $this->ciDatabaseValues['dbprefix'] ?? '',
                'prefix_indexes' => true,
                'schema' => 'public',
                'sslmode' => 'prefer',
            ],
            'sqlsrv' => [
                'driver' => $driver,
                'charset' => $this->ciDatabaseValues['char_set'] ?? 'utf8',
                'prefix' => $this->ciDatabaseValues['dbprefix'] ?? '',
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
