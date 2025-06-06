<?php

namespace App\Services;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class StatusBarService
{
    protected $progressBar;
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function start(string $message, int $totalSteps): void
    {
        $this->output->writeln("<info>{$message}</info>");
        $this->progressBar = new ProgressBar($this->output, $totalSteps);
        $this->progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $this->progressBar->setMessage('Starting...');
        $this->progressBar->start();
    }

    public function advance(string $message): void
    {
        if ($this->progressBar) {
            $this->progressBar->setMessage($message);
            $this->progressBar->advance();
        }
    }

    public function finish(string $message): void
    {
        if ($this->progressBar) {
            $this->progressBar->setMessage($message);
            $this->progressBar->finish();
            $this->output->writeln(''); // Add a newline for better formatting
        }
    }

    public function error(string $message): void
    {
        if ($this->progressBar) {
            $this->progressBar->setMessage("<error>{$message}</error>");
            $this->progressBar->finish();
            $this->output->writeln('');
        } else {
            $this->output->writeln("<error>{$message}</error>");
        }
    }
}