<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('risk:check-log-gaps')
            ->dailyAt('08:00')
            ->timezone('Asia/Manila')
            ->withoutOverlapping();

        $schedule->command('logs:prune-activity --days=180')
            ->dailyAt('02:00')
            ->timezone('Asia/Manila');

        $schedule->command('auth:clear-resets')
            ->dailyAt('02:30')
            ->timezone('Asia/Manila');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}