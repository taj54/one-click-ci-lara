<?php

namespace App\Services\Coordinators;

use App\Contracts\CIAnalyzerInterface;
use App\Contracts\CIConverterInterface;
use App\Enums\CIVersion;

abstract class AbstractCIMigrationCoordinatorService
{
    /**
     * @param CIConverterInterface[] $converters
     * @param CIAnalyzerInterface[] $analyzers
     */
    public function __construct(
        protected iterable $analyzers,
        protected iterable $converters
    ) {}

    /**
     * Find the first converter that supports the specified version.
     */
    protected function resolveConverterFor(CIVersion $version): ?CIConverterInterface
    {
        foreach ($this->converters as $converter) {
            if ($converter instanceof CIConverterInterface && $converter->supports($version)) {
                return $converter;
            }
        }

        return null;
    }
    protected function resolveAnalyzerFor(CIVersion $version): ?CIAnalyzerInterface
    {
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer->supports($version)) {
                return $analyzer;
            }
        }

        return null;
    }
}
