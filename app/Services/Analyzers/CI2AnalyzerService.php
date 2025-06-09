<?php

namespace App\Services\Analyzers;

use App\Contracts\CIAnalyzerInterface;
use App\Enums\CIVersion;
use App\Factories\CIControllerProcessorFactory;
use App\Factories\CIModelProcessorFactory;
use App\Factories\CIRouteProcessorFactory;

class CI2AnalyzerService extends AbstractCIAnalyzerService implements CIAnalyzerInterface
{
    public function supports(CIVersion $version): bool
    {
        return $version === CIVersion::CI2;
    }

    protected function getProcessorFactories(): array
    {
        $version = CIVersion::CI2;

        return [
            'controllers' => CIControllerProcessorFactory::make($version),
            'models'      => CIModelProcessorFactory::make($version),
            'routes'      => CIRouteProcessorFactory::make($version),
        ];
    }
}
