<?php

namespace App\Traits;

trait HasDirectories
{
    protected $testEnvDir;
    protected $inputDirectory;
    protected $outputDirectory;
    protected $version;
    protected $codeIgnatorVersion;

    public function setTestEnvDir($testEnvDir)
    {
        $this->testEnvDir = rtrim(str_replace(['/', '\\'], '\\', $testEnvDir), '\\');
    }

    public function setInputDirectory($inputDirectory)
    {
        if (!empty($this->testEnvDir)) {
            $path = $this->testEnvDir . '\\' . ltrim($inputDirectory, '\\/');
        } else {
            $path = $inputDirectory;
        }
        $this->inputDirectory = rtrim(str_replace(['/', '\\'], '\\', $path), '\\');
    }

    public function setOutputDirectory($outputDirectory)
    {
        if (!empty($this->testEnvDir)) {
            $path = $this->testEnvDir . '\\' . ltrim($outputDirectory, '\\/');
        } else {
            $path = $outputDirectory;
        }
        $this->outputDirectory = rtrim(str_replace(['/', '\\'], '\\', $path), '\\');
    }

    public function getInputDirectory()
    {
        return $this->inputDirectory;
    }

    public function getOutputDirectory()
    {
        return $this->outputDirectory;
    }
    public function getCISystemDirectory()
    {
        return  rtrim($this->inputDirectory, '/\\') . DIRECTORY_SEPARATOR . 'system';
    }
    public function getCICoreDirectory()
    {
        return $this->getCISystemDirectory() . DIRECTORY_SEPARATOR . 'core';
    }
    public function getCICompatDirectory()
    {
        return  $this->getCICoreDirectory() . DIRECTORY_SEPARATOR . 'compat';
    }
    protected function getCIProjectDirectory()
    {
        if ($this->getCodeIgniterVersion() == 'CI4') {
            $path = $this->inputDirectory . DIRECTORY_SEPARATOR . 'app';
        } else {
            $path = $this->inputDirectory . DIRECTORY_SEPARATOR . 'application';
        }

        return $path;
    }

    public function setCodeIgniterVersion($version)
    {
        $this->codeIgnatorVersion = $version;
    }


    public function getCodeIgniterVersion()
    {
        return $this->codeIgnatorVersion;
    }
    
    protected function getLaravelConfigPath($projectName)
    {
        return $this->outputDirectory . DIRECTORY_SEPARATOR . $projectName;
    }
}
