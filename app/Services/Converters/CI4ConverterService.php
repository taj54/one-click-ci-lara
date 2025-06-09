<?php

namespace App\Services\Converters;

use App\Contracts\CIConverterInterface;
use App\Enums\CIVersion;
use App\Factories\CIConfigProcessorFactory;
use App\Factories\CIDatabaseProcessorFactory;
use RuntimeException;

class CI4ConverterService extends AbstractCIConverterService implements CIConverterInterface
{
    public function supports(CIVersion $version): bool
    {
        return $version === CIVersion::CI4;
    }

    protected function parseCIConfig(): bool
    {
        try {
            $processor = CIConfigProcessorFactory::make(CIVersion::CI4);
            $this->ciConfigValues = $this->phpFileParser
                ->parse($this->getCIConfigFile(), $processor)
                ->getResults();
            return true;
        } catch (RuntimeException $e) {
            return $this->fail("⚠️ Error parsing CI4 config.php: " . $e->getMessage());
        }
    }

    protected function parseCIDatabase(): bool
    {
        try {
            $processor = CIDatabaseProcessorFactory::make(CIVersion::CI4);
            $this->ciDatabaseValues = $this->phpFileParser
                ->parse($this->getCIDataBaseFile(), $processor)
                ->getResults();

            return !empty($this->ciDatabaseValues);
        } catch (RuntimeException $e) {
            return $this->fail("⚠️ Error parsing CI4 database.php: " . $e->getMessage());
        }
    }
}
