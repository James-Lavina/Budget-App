<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneActivityLogs extends Command
{
    protected $signature = 'logs:prune-activity {--days=180 : Delete records older than this many days (minimum 30)}';

    protected $description = 'Deletes old activity logs (IP addresses and user agents) and old read notifications.';

    public function handle()
    {
        $days   = max(30, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $logs = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $notifications = DatabaseNotification::whereNotNull('read_at')
            ->where('read_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$logs} activity log(s) and {$notifications} read notification(s) older than {$days} days.");

        return 0;
    }
}