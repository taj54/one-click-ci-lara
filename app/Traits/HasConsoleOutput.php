<?php

namespace App\Traits;

use Symfony\Component\Console\Output\OutputInterface;

trait HasConsoleOutput
{
    protected ?OutputInterface $output = null;

    /**
     * Set the console output implementation.
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Get the console output implementation.
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }
}
