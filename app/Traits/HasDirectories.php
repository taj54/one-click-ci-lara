<?php

namespace App\Traits;

use App\Enums\CIVersion;
use App\Support\DirectoryManager;
use Directory;

trait HasDirectories
{
    private string $testEnvDir = '';
    private CIVersion $codeIgnitorVersion = CIVersion::UNKNOWN;

    public function setCodeIgniterVersion(CIVersion  $version): bool
    {
        $this->codeIgnitorVersion = $version;
        return true;
    }

    public function getCodeIgniterVersion(): CIVersion 
    {
        return $this->codeIgnitorVersion;
    }
    

    public function setTestEnvDir(string $testEnvDir): void
    {
        $this->testEnvDir = $this->normalizePath($testEnvDir);
    }

    public function setInputDirectory(string $inputDirectory): bool
    {
        if ($this->isAbsolutePath($inputDirectory)) {
            $path = $inputDirectory;
        } else {
            $path = $this->prependTestEnvDir($inputDirectory);
        }
        DirectoryManager::getInstance()->setInputDirectory($path);
        return true;
    }

    public function setOutputDirectory(string $outputDirectory): bool
    {
        if ($this->isAbsolutePath($outputDirectory)) {
            $path = $outputDirectory;
        } else {
            $path = $this->prependTestEnvDir($outputDirectory);
        }
        DirectoryManager::getInstance()->setOutputDirectory($path);
        return true;
    }

    public function getInputDirectory(): string
    {
        $path = DirectoryManager::getInstance()->getInputDirectory();
        return  $path;
    }

    public function getOutputDirectory(): string
    {
        return DirectoryManager::getInstance()->getOutputDirectory();
    }

    public function getCISystemDirectory(): string
    {
        return $this->normalizePath($this->getInputDirectory() . DIRECTORY_SEPARATOR . 'system');
    }

    public function getCICoreDirectory(): string
    {
        return $this->normalizePath($this->getCISystemDirectory() . DIRECTORY_SEPARATOR . 'core');
    }

    public function getCICompatDirectory(): string
    {
        return $this->normalizePath($this->getCICoreDirectory() . DIRECTORY_SEPARATOR . 'compat');
    }

    public function getCIProjectDirectory(): string
    {
        $folder = $this->getCodeIgniterVersion() === 'CI4' ? 'app' : 'application';
        DirectoryManager::getInstance()->setCIProjectDirectory($folder);
        return DirectoryManager::getInstance()->getCIProjectDirectory();
    }

    public function getCIConfigDirectory(): string
    {
        return $this->normalizePath($this->getCIProjectDirectory() . DIRECTORY_SEPARATOR . 'config');
    }

    public function getCIConfigFile(): string
    {
        return $this->normalizePath($this->getCIConfigDirectory() . DIRECTORY_SEPARATOR . 'config.php');
    }

    public function getCIDataBaseFile(): string
    {
        return $this->normalizePath($this->getCIConfigDirectory() . DIRECTORY_SEPARATOR . 'database.php');
    }
    public function setLaravelProjectName(string $projectName)
    {
        DirectoryManager::getInstance()->setLaravelProjectName($projectName);
        return true;
    }


    public function getLaravelProjectDirectory(): string
    {
        return  $this->normalizePath(
            $this->getOutputDirectory() . DIRECTORY_SEPARATOR . DirectoryManager::getInstance()->getLaravelProjectName()
        );
    }

    public function getLaravelENVFile(): string
    {
        return $this->normalizePath($this->getLaravelProjectDirectory() . DIRECTORY_SEPARATOR . '.env');
    }
    public function getLaravelDataBaseFile(): string
    {
        return $this->normalizePath($this->getLaravelProjectDirectory() . DIRECTORY_SEPARATOR . 'config'.DIRECTORY_SEPARATOR.'database.php');
    }

    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    protected function prependTestEnvDir(string $path): string
    {
        $normalized = ltrim($path, '/\\');
        return !empty($this->testEnvDir)
            ? $this->normalizePath($this->testEnvDir . DIRECTORY_SEPARATOR . $normalized)
            : $this->normalizePath($normalized);
    }
    protected function isAbsolutePath(string $path): bool
    {
        // Handles Windows (C:\...) and Unix (/...)
        return preg_match('/^(?:[a-zA-Z]:)?[\/\\\\]/', $path) === 1;
    }
}
