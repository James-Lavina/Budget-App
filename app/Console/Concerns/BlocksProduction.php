<?php

namespace App\Console\Concerns;

trait BlocksProduction
{
    protected function blockedInProduction(): bool
    {
        if (app()->environment('production')) {
            $this->error('This command is disabled in production.');
            return true;
        }

        return false;
    }
}