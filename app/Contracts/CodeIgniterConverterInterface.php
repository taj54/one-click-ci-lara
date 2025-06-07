<?php

namespace App\Contracts;

use App\Enums\CIVersion;

interface CodeIgniterConverterInterface
{
    public function supports(CIVersion $version): bool;

    public function convert(): bool;
}
