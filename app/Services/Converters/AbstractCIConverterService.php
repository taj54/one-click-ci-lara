<?php

namespace App\Services\Converters;

use App\Contracts\CIConverterInterface;
use App\Enums\CIVersion;
use App\Enums\LaravelDatabaseDriver;
use App\Services\FileHandlerService;
use App\Services\LogService;
use App\Services\Utility\PhpFileParser;
use App\Support\DatabaseConnectionConfig;
use App\Traits\HasDirectories;
use RuntimeException;

abstract class AbstractCIConverterService implements CIConverterInterface
{
    use HasDirectories;

    protected array $ciConfigValues = [];
    protected array $ciDatabaseValues = [];

    public function __construct(
        protected LogService $logService,
        protected PhpFileParser $phpFileParser,
        protected FileHandlerService $fileHandlerService
    ) {}

    abstract public function supports(CIVersion $version): bool;
    abstract protected function parseCIConfig(): bool;
    abstract protected function parseCIDatabase(): bool;

    public function convert(): bool
    {
        $parsed = $this->parseCIConfig() && $this->parseCIDatabase();
        if (!$parsed) {
            return $this->fail("❌ Failed to parse both CI config and database files. No updates applied.");
        }

        $envPath = $this->getLaravelENVFile();
        $dbPath = $this->getLaravelDataBaseFile();

        if (!$this->validateLaravelFiles($envPath, $dbPath)) {
            return false;
        }

        $envContent = file_get_contents($envPath);
        $dbContent = file_get_contents($dbPath);

        if ($envContent === false || $dbContent === false) {
            return $this->fail("❌ Failed to read one or more Laravel config files.");
        }

        $this->prepareDatabaseValues();

        $envUpdates = $this->buildEnvUpdates();
        $dbUpdates = $this->buildDatabaseConfigUpdates();

        if (!empty($dbUpdates) && !$this->updateDatabaseConfig($dbPath, $envUpdates['DB_CONNECTION'], $dbUpdates)) {
            return false;
        }

        $updatedEnv = $this->applyEnvUpdates($envContent, $envUpdates);

        if (file_put_contents($envPath, $updatedEnv) === false) {
            return $this->fail("❌ Failed to write updated .env file at: {$envPath}");
        }

        $this->logService->info("✅ .env file updated with: " . implode(', ', array_keys($envUpdates)));
        return true;
    }

    protected function prepareDatabaseValues(): void
    {
        $ciDriver = strtolower($this->ciDatabaseValues['dbdriver'] ?? 'mysql');
        $this->ciDatabaseValues['dbdriver'] = LaravelDatabaseDriver::fromCIDriver($ciDriver)->value;
    }

    protected function buildDatabaseConfigUpdates(): array
    {
        $host = $this->ciDatabaseValues['hostname'] ?? '127.0.0.1';
        [$dbHost, $dbPort] = array_pad(explode(':', $host), 2, null);

        $wrapper = new DatabaseConnectionConfig(
            LaravelDatabaseDriver::fromCIDriver($this->ciDatabaseValues['dbdriver']),
            $this->ciDatabaseValues,
            is_numeric($dbPort) ? (int) $dbPort : null
        );

        return $wrapper->toLaravelConfig();
    }

    protected function buildEnvUpdates(): array
    {
        $driver = $this->ciDatabaseValues['dbdriver'];
        $host = preg_replace('/^https?:\/\//', '', $this->ciDatabaseValues['hostname'] ?? '127.0.0.1');
        [$dbHost, $dbPort] = array_pad(explode(':', $host), 2, null);

        $portWrapper = new DatabaseConnectionConfig(
            LaravelDatabaseDriver::fromCIDriver($driver),
            [],
            is_numeric($dbPort) ? (int)$dbPort : null
        );

        $appUrl = preg_replace('/^https?:\/\//', '', $this->ciConfigValues['base_url'] ?? 'http://localhost');

        return [
            'APP_NAME'     => $this->ciConfigValues['site_title']
                ?? $this->ciConfigValues['site_name']
                ?? 'Laravel Migration App',
            'APP_ENV'      => 'local',
            'APP_DEBUG'    => !empty($this->ciConfigValues['debug']) ? 'true' : 'false',
            'APP_URL'      => $appUrl,
            'DB_CONNECTION'=> $driver,
            'DB_HOST'      => $dbHost,
            'DB_PORT'      => $portWrapper->resolvedPort(),
            'DB_DATABASE'  => $this->ciDatabaseValues['database'] ?? '',
            'DB_USERNAME'  => $this->ciDatabaseValues['username'] ?? '',
            'DB_PASSWORD'  => $this->ciDatabaseValues['password'] ?? '',
        ];
    }

    protected function applyEnvUpdates(string $envContent, array $updates): string
    {
        foreach ($updates as $key => $value) {
            $envContent = $this->setEnvValue($envContent, $key, $value);
        }

        return $envContent;
    }

    protected function updateDatabaseConfig(string $filePath, string $driver, array $updates): bool
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            return $this->fail("❌ Failed to read database config file.");
        }

        $pattern = "/(['\"]{$driver}['\"]\s*=>\s*\[)(.*?)(\n\s*],)/ms";
        if (!preg_match($pattern, $fileContent, $matches, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("❌ Could not locate '{$driver}' config in the file.");
        }

        [$header, $body, $footer] = [$matches[1][0], $matches[2][0], $matches[3][0]];

        foreach ($updates as $key => $value) {
            $valExported = var_export($value, true);
            $keyPattern = "/(['\"]{$key}['\"]\s*=>\s*)([^,\n]+)(,?)(\s*\/\/[^\n]*)?/";

            if (preg_match($keyPattern, $body)) {
                $body = preg_replace($keyPattern, "'{$key}' => {$valExported},", $body);
            } else {
                $body .= "\n        '{$key}' => {$valExported},";
            }
        }

        $fileContent = substr_replace($fileContent, $header . $body . $footer, $matches[0][1], strlen($matches[0][0]));

        if (file_put_contents($filePath, $fileContent) === false) {
            return $this->fail("❌ Failed to write updated DB config to: {$filePath}");
        }

        $this->logService->info("🔁 Updated DB config for '{$driver}' with: " . implode(', ', array_keys($updates)));
        return true;
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

    protected function validateLaravelFiles(string $envPath, string $dbPath): bool
    {
        return $this->fileHandlerService->validateFileExists($envPath)
            && $this->fileHandlerService->validateFileExists($dbPath);
    }

    protected function fail(string $message): bool
    {
        $this->logService->error($message);
        return false;
    }
}
