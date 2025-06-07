<?php

namespace App\Enums;

enum CIVersion: string
{
    case CI2 = 'ci2';
    case CI3 = 'ci3';
    case CI4 = 'ci4';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::CI2 => 'CodeIgniter 2',
            self::CI3 => 'CodeIgniter 3',
            self::CI4 => 'CodeIgniter 4',
            
        };
    }
    public function shortLabel(): string
    {
        return match ($this) {
            self::CI2 => 'CI2',
            self::CI3 => 'CI3',
            self::CI4 => 'CI4',
        };
    }

    public static function fromInput(string $input): self
    {
        return match (strtolower($input)) {
            'ci2', 'codeigniter2' => self::CI2,
            'ci3', 'codeigniter3' => self::CI3,
            'ci4', 'codeigniter4' => self::CI4,
            'unknown' => self::UNKNOWN,
            default => throw new \InvalidArgumentException("Unsupported CodeIgniter version: {$input}"),
        };
    }
}
