<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NoExpenseLogsWarning extends Notification
{
    use Queueable;

    public $daysSinceLastLog;

    public function __construct($daysSinceLastLog)
    {
        $this->daysSinceLastLog = $daysSinceLastLog;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'anomaly_type'  => 'no_expense_logs',
            'severity_tier' => 'low',
            'description'   => "Tracking Check-In 📋: You haven't logged an expense in {$this->daysSinceLastLog} days. Log a purchase to keep your budget accurate.",
            'resolved'      => false,
        ];
    }
}