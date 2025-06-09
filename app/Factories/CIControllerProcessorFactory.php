<?php

namespace App\Factories;

use App\Contracts\NodeProcessorInterface;
use App\Enums\CIVersion;
use App\Services\Parsers\NodeProcessors\CI2\CI2ControllerNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI3\CI3ControllerNodeProcessor;
use App\Services\Parsers\NodeProcessors\CI4\CI4ControllerNodeProcessor;
use InvalidArgumentException;

class CIControllerProcessorFactory
{
    /**
     * Create a processor instance based on CI version.
     *
     * @param CIVersion $version
     * @return NodeProcessorInterface
     */
    public static function make(CIVersion $version): NodeProcessorInterface
    {
        return match($version) {
            CIVersion::CI2 => new CI2ControllerNodeProcessor(),
            CIVersion::CI3 => new CI3ControllerNodeProcessor(),
            CIVersion::CI4 => new CI4ControllerNodeProcessor(),
            default => throw new InvalidArgumentException("Unsupported CI version: {$version->name}"),
        };
    }
}
