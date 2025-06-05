<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;

class LogService
{
    protected $console;

    public function __construct(OutputInterface $console)
    {
        $this->console = $console;
    }

    public function info($message)
    {
        Log::info($message);
        $this->console->writeln("<info>{$message}</info>");
    }

    public function error($message)
    {
        Log::error($message);
        $this->console->writeln("<error>{$message}</error>");
    }

    public function warning($message)
    {
        Log::warning($message);
        $this->console->writeln("<comment>{$message}</comment>");
    }

    public function debug($message)
    {
        Log::debug($message);
        $this->console->writeln("<comment>{$message}</comment>");
    }

    public function comment($message)
    {
        Log::info($message);
        $this->console->writeln("<comment>{$message}</comment>");
    }
}
