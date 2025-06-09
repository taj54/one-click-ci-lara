<?php

namespace App\Services\Coordinators;

use App\Contracts\CIMigrationCoordinatorInterface;
use App\Enums\CIVersion;
use App\Services\Analyzers\CIAnalyzerService;

class CI3MigrationCoordinatorService extends AbstractCIMigrationCoordinatorService implements CIMigrationCoordinatorInterface
{
    public function __construct(
        iterable $analyzers,
        iterable $converters
    ) {
        parent::__construct($analyzers,$converters);
    }

    public function supports(CIVersion $version): bool
    {
        
        return $version === CIVersion::CI3;
    }

    public function executeMigration(): array
    {
        $version = CIVersion::CI3;
        $report = [
            'analysis' => [],
            'conversion' => [],
        ];

        $converter = $this->resolveConverterFor($version);
        $analyzer = $this->resolveAnalyzerFor($version);


        if (!$converter) {
            $report['conversion'] = [
                'success' => false,
                'error' => "❌ No converter found for CodeIgniter version: {$version->name}",
            ];
            return $report;
        }
        if(!$analyzer){
            $report['analysis'] = [
                'success' => false,
                'error' => "❌ No analyzer found for CodeIgniter version: {$version->name}",
            ];
            return $report;
        }
        $report['analysis']['success'] = $analyzer->analyze();
        $report['conversion']['success'] = $converter->convert();

        return $report;
    }
}
