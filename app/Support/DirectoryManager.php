<?php

namespace App\Support;

class DirectoryManager
{
    private static ?self $instance = null;

    private string $inputDirectory = '';
    private string $outputDirectory = '';
    private string $CIProjectDirectory = '';
    private string $laravelProjectName = '';
    private function __construct() {} // private constructor to enforce singleton

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setInputDirectory(string $path): void
    {
        $this->inputDirectory = $this->normalizePath($path);
    }

    public function getInputDirectory(): string
    {
        return $this->normalizePath($this->inputDirectory);
    }

    public function clearInputDirectory(): void
    {
        $this->inputDirectory = '';
    }

    public function setOutputDirectory(string $path): void
    {
        $this->outputDirectory = $this->normalizePath($path);
    }

    public function getOutputDirectory(): string
    {
        return $this->normalizePath($this->outputDirectory);
    }

    public function clearOutputDirectory(): void
    {
        $this->outputDirectory = '';
    }
    public function setCIProjectDirectory(string $path)
    {
        $this->CIProjectDirectory = $this->normalizePath($this->getInputDirectory() . DIRECTORY_SEPARATOR . $path);
    }
    public function getCIProjectDirectory()
    {
        return $this->normalizePath($this->CIProjectDirectory);
    }
    public function setLaravelProjectName($projectName)
    {
        $this->laravelProjectName = $projectName;
        return true;
    }
    public function getLaravelProjectName()
    {
        return  $this->laravelProjectName;
    }
    public function clearLaravelProjectName()
    {
          $this->laravelProjectName='';
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
