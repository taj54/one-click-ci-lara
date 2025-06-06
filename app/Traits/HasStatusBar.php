<?php

namespace App\Traits;

use App\Services\StatusBarService;

trait HasStatusBar
{
    public StatusBarService $statusBarService;

    public function setStatusBar(StatusBarService $statusBarService): void
    {
        $this->statusBarService = $statusBarService;
    }

    protected function statusBar(): StatusBarService
    {
        return $this->statusBarService;
    }
}