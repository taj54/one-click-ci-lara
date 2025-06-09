<?php

namespace App\Factories;

use App\Contracts\NodeProcessorInterface;
use App\Enums\CIVersion;
use App\Services\Parsers\NodeProcessors\CI2\CI2DatabaseNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI3\CI3DatabaseNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI4\CI4DatabaseNodeProcessor;
use InvalidArgumentException;

/**
 * Factory for creating the correct CI database node processor based on version.
 */
class CIDatabaseProcessorFactory
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
            CIVersion::CI2 => new CI2DatabaseNodeProcessor(),
            CIVersion::CI3 => new CI3DatabaseNodeProcessor(),
            CIVersion::CI4 => new CI4DatabaseNodeProcessor(),
            default => throw new InvalidArgumentException("Unsupported CodeIgniter version: {$version->name}"),
        };
    }
}
