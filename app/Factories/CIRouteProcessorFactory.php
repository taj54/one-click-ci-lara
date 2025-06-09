<?php

namespace App\Factories;

use App\Contracts\NodeProcessorInterface;
use App\Enums\CIVersion;
use App\Services\Parsers\NodeProcessors\CI2\CI2RouteNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI3\CI3RouteNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI4\CI4RouteNodeProcessor;
use InvalidArgumentException;

/**
 * Factory to create version-specific CI route node processors.
 */
class CIRouteProcessorFactory
{
    /**
     * Create a processor instance based on CI version.
     *
     * @param CIVersion $version
     * @return NodeProcessorInterface
     */
    public static function make(CIVersion $version): NodeProcessorInterface
    {
        return match ($version) {
            CIVersion::CI2 => new CI2RouteNodeProcessor(),
            CIVersion::CI3 => new CI3RouteNodeProcessor(),
            CIVersion::CI4 => new CI4RouteNodeProcessor(),
            default => throw new InvalidArgumentException("Unsupported CI version: {$version->name}"),
        };
    }
}
