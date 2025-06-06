<?php

namespace App\Services;

use App\Services\Parsers\NodeProcessors\CIConfigNodeProcessor;
use App\Services\Parsers\NodeProcessors\CIDatabaseNodeProcessor;
use App\Services\Utility\PhpFileParser;
use App\Traits\HasDirectories;
use RuntimeException;

class CI3ConverterService
{
    use HasDirectories;

    protected LogService $logService;
    protected PhpFileParser $phpFileParser;

    /**
     * Constructor to initialize the LogService and PhpFileParser.
     */
    public function __construct(LogService $logService, PhpFileParser $phpFileParser)
    {
        $this->logService = $logService;
        $this->phpFileParser = $phpFileParser;
    }

    public function convert()
    {

        // Ensure BASEPATH is defined for any CI files that might need it
        // if (!defined('BASEPATH')) {
        //     define('BASEPATH', true);
        // }

        $this->convertConfigToEnv();
        $this->convertDatabaseToEnv();
    }

    /**
     * Converts CodeIgniter 3 config.php values to Laravel's .env file.
     *
     * @param string $ciBasePath The base path to the CodeIgniter 3 application.
     * @param string $laravelBasePath The base path to the Laravel application.
     * @return bool True on success, false on failure.
     */
    public function convertConfigToEnv(): bool
    {
        $laravelConfigMap = [];

        try {
            /** @var CIConfigNodeProcessor $processor */
            // Use the injected PhpFileParser instance
            $processor = $this->phpFileParser->parse($this->getCIConfigFile(), new CIConfigNodeProcessor());
            $ciConfigValues = $processor->getResults(); // Get results from the processor

            // Map CI config values to Laravel .env keys
            $laravelConfigMap = [
                'APP_NAME' => $ciConfigValues['site_title'] ?? ($ciConfigValues['site_name'] ?? 'Laravel App'),
                'APP_ENV' => 'local', // Defaulting to 'local', could be dynamic if needed
                'APP_DEBUG' => ($ciConfigValues['debug'] ?? false) ? 'true' : 'false',
                'APP_URL' => $ciConfigValues['base_url'] ?? 'http://localhost',
            ];
        } catch (RuntimeException $e) {
            $this->logService->error("Error converting CI config.php: " . $e->getMessage());
            return false;
        }

        $envPath = $this->getLaravelENVFile();
        $this->logService->info("Attempting to update .env at: $envPath");

        if (!file_exists($envPath)) {
            $this->logService->error(".env file not found at: $envPath. Cannot update config.");
            return false;
        }

        $envContent = file_get_contents($envPath);
        if ($envContent === false) {
            $this->logService->error("Failed to read .env file at: $envPath.");
            return false;
        }

        foreach ($laravelConfigMap as $key => $value) {
            // Special handling for APP_NAME to ensure quotes if it contains spaces
            $processedValue = ($key === 'APP_NAME') ? '"' . addslashes($value) . '"' : $value;
            $envContent = $this->setEnvValue($envContent, $key, $processedValue);
        }

        if (file_put_contents($envPath, $envContent) === false) {
            $this->logService->error("Failed to write to .env file at: $envPath.");
            return false;
        }

        $this->logService->info(".env file updated successfully with CI config values.");
        return true;
    }

    /**
     * Converts CodeIgniter 3 database.php settings to Laravel's .env file.
     *
     * @param string $ciBasePath The base path to the CodeIgniter 3 application.
     * @param string $laravelBasePath The base path to the Laravel application.
     * @return bool True on success, false on failure.
     * @throws RuntimeException If CI3 database.php or Laravel .env file is not found or unreadable.
     */
    public function convertDatabaseToEnv(): bool
    {


        try {
            /** @var CIDatabaseNodeProcessor $processor */
            // Use the injected PhpFileParser instance
            $processor = $this->phpFileParser->parse($this->getCIDataBaseFile(), new CIDatabaseNodeProcessor());
            $dbValues = $processor->getResults(); // Get results from the processor
        } catch (RuntimeException $e) {
            $this->logService->error("Error converting CI database.php: " . $e->getMessage());
            throw $e; // Re-throw the exception as it's a critical parsing error
        }

        if (empty($dbValues)) {
            $this->logService->comment("No \$db['default'] config found in: " . $this->getCIDataBaseFile() . ". Skipping database .env conversion.");
            return false; // Not necessarily an error, just nothing to convert
        }

        // Map CI config to Laravel .env keys
        $envUpdates = [
            'DB_CONNECTION' => $dbValues['dbdriver'] ?? 'mysql',
            'DB_HOST' => $dbValues['hostname'] ?? '127.0.0.1',
            'DB_PORT' => '3306', // Default MySQL port
            'DB_DATABASE' => $dbValues['database'] ?? '',
            'DB_USERNAME' => $dbValues['username'] ?? '',
            'DB_PASSWORD' => $dbValues['password'] ?? '',
            'DB_CHARSET' => $dbValues['char_set'] ?? 'utf8mb4',
            'DB_COLLATION' => $dbValues['dbcollat'] ?? 'utf8mb4_unicode_ci',
        ];
        $envPath = $this->getLaravelENVFile();
        $envContent = file_get_contents($envPath);
        if ($envContent === false) {
            $this->logService->error("Failed to read .env file at:  $envPath.");
            throw new RuntimeException("Failed to read .env file at:  $envPath.");
        }

        foreach ($envUpdates as $key => $value) {
            $envContent = $this->setEnvValue($envContent, $key, $value);
        }

        if (file_put_contents( $envPath, $envContent) === false) {
            $this->logService->error("Failed to write to .env file at:  $envPath.");
            return false;
        }

        $this->logService->info(".env file updated successfully with CI database values.");
        return true;
    }

    /**
     * Sets or updates an environment variable in the .env file content.
     *
     * @param string $envContents The current content of the .env file.
     * @param string $key The environment variable key.
     * @param string|int|bool|null $value The value to set.
     * @return string The updated content of the .env file.
     */
    protected function setEnvValue(string $envContents, string $key, string|int|bool|null $value): string
    {
        $value = (string) $value; // Ensure value is a string for writing

        $pattern = "/^{$key}=.*/m";
        $line = "{$key}={$value}";

        if (preg_match($pattern, $envContents)) {
            return preg_replace($pattern, $line, $envContents);
        }

        // If the key doesn't exist, append it. Try to append it near relevant existing keys.
        $keyPrefix = explode('_', $key)[0];
        if (preg_match_all("/^{$keyPrefix}_.*/m", $envContents, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $pos = $lastMatch[1] + strlen($lastMatch[0]);
            return substr_replace($envContents, PHP_EOL . $line, $pos, 0);
        }

        // If no relevant prefix found, just append to the end with a newline
        return rtrim($envContents, "\n") . "\n{$line}\n";
    }
}
