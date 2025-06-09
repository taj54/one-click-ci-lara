<?php

namespace App\Factories;

use App\Contracts\NodeProcessorInterface;
use App\Enums\CIVersion;
use App\Services\Parsers\NodeProcessors\CI2\CI2ModelNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI3\CI3ModelNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI4\CI4ModelNodeProcessor;
use InvalidArgumentException;

class CIModelProcessorFactory
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
            CIVersion::CI2 => new CI2ModelNodeProcessor(),
            CIVersion::CI3 => new CI3ModelNodeProcessor(),
            CIVersion::CI4 => new CI4ModelNodeProcessor(),
            default => throw new InvalidArgumentException("Unsupported CI version: {$version->name}"),
        };
    }
}
