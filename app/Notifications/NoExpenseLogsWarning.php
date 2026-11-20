<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

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
        $days = (int) $this->daysSinceLastLog;

        return [
            'anomaly_type'  => 'no_expense_logs',
            'severity_tier' => 'low',
            'title'         => 'Time to log your spending',
            'description'   => "You haven't logged an expense in {$days} " . Str::plural('day', $days)
                . '. Add your recent purchases so your daily limit stays accurate.',
            'resolved'      => false,
        ];
    }
}