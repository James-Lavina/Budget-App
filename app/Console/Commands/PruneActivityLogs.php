<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\RegistrationOtp;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneActivityLogs extends Command
{
    protected $signature = 'logs:prune-activity {--days=180 : Delete records older than this many days (minimum 30)}';

    protected $description = 'Deletes old activity logs (IP addresses and user agents), old read notifications, and abandoned registration codes.';

    public function handle()
    {
        $days   = max(30, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $logs = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $notifications = DatabaseNotification::whereNotNull('read_at')
            ->where('read_at', '<', $cutoff)
            ->delete();

        $otps = RegistrationOtp::where('expires_at', '<', now()->subDay())->delete();

        $this->info("Deleted {$logs} activity log(s), {$notifications} read notification(s) older than {$days} days, and {$otps} abandoned registration code(s).");

        return 0;
    }
}