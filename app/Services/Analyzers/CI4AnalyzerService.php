<?php

namespace App\Services\Analyzers;

use App\Enums\CIVersion;
use App\Factories\CIControllerProcessorFactory;
use App\Factories\CIModelProcessorFactory;
use App\Factories\CIRouteProcessorFactory;

class CI4AnalyzerService extends AbstractCIAnalyzerService
{
    public function supports(CIVersion $version): bool
    {
        return $version === CIVersion::CI4;
    }

    protected function getProcessorFactories(): array
    {
        $version = CIVersion::CI4;

        return [
            'controllers' => CIControllerProcessorFactory::make($version),
            'models'      => CIModelProcessorFactory::make($version),
            'routes'      => CIRouteProcessorFactory::make($version),
        ];
    }
}
