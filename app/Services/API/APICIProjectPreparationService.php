<?php

namespace App\Services\API;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;
use App\Enums\CIVersion;

class APICIProjectPreparationService
{
    public function extractAndDetect(string $zipPath): array
    {
        $uniqueId = Str::uuid()->toString();
        $testEnvDirectory = realpath(base_path('..' . DIRECTORY_SEPARATOR . 'test-environment'));
        $unzipPath = $testEnvDirectory . DIRECTORY_SEPARATOR . $uniqueId;

        File::makeDirectory($unzipPath, 0755, true, true);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Could not open ZIP file.");
        }

        $zip->extractTo($unzipPath);
        $zip->close();

        $ciProjectPath = $this->resolveProjectRoot($unzipPath);

        return [
            'uniqueId' => $uniqueId,
            'projectPath' => $ciProjectPath,
        ];
    }

    public function getProjectPathFromUniqueId(string $uniqueId): string
    {
        $testEnvDirectory = realpath(base_path('..' . DIRECTORY_SEPARATOR . 'test-environment'));
        $unzipPath = $testEnvDirectory . DIRECTORY_SEPARATOR . $uniqueId;

        if (!File::exists($unzipPath)) {
            throw new \RuntimeException("Project path not found.");
        }

        return $this->resolveProjectRoot($unzipPath);
    }

    protected function resolveProjectRoot(string $unzipPath): string
    {
        $items = array_values(array_filter(scandir($unzipPath), fn($i) => $i !== '.' && $i !== '..'));

        if (count($items) === 1 && File::isDirectory($unzipPath . DIRECTORY_SEPARATOR . $items[0])) {
            return $unzipPath . DIRECTORY_SEPARATOR . $items[0];
        }

        return $unzipPath;
    }
}
