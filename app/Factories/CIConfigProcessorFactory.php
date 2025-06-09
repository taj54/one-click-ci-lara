<?php

namespace App\Factories;

use App\Services\Parsers\NodeProcessors\CI2\CI2ConfigNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI3\CI3ConfigNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI4\CI4ConfigNodeProcessor;
use App\Contracts\NodeProcessorInterface;
use App\Enums\CIVersion;
use InvalidArgumentException;

class CIConfigProcessorFactory
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
            CIVersion::CI2 => new CI2ConfigNodeProcessor(),
            CIVersion::CI3 => new CI3ConfigNodeProcessor(),
            CIVersion::CI4 => new CI4ConfigNodeProcessor(),
            default => throw new InvalidArgumentException("Unsupported CI version: $version->name"),
        };
    }
}
