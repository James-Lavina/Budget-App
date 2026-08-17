<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PacingWarning extends Notification
{
    use Queueable;

    public $percentageSpent;
    public $daysRemaining;
    public $remainingBalance;

    public function __construct($percentageSpent, $daysRemaining, $remainingBalance = null)
    {
        $this->percentageSpent = $percentageSpent;
        $this->daysRemaining = max(1, $daysRemaining);
        $this->remainingBalance = $remainingBalance;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $dailyCapText = '';
        if ($this->remainingBalance !== null && $this->remainingBalance > 0) {
            $dailyCap = number_format($this->remainingBalance / $this->daysRemaining, 2);
            $dailyCapText = " Stretch your remaining ₱" . number_format($this->remainingBalance, 2) . " by keeping under ₱{$dailyCap}/day.";
        }

        return [
            'anomaly_type'  => 'pacing_warning',
            'severity_tier' => 'medium',
            'description'   => "Slow Your Roll ⚡: You've used {$this->percentageSpent}% of your weekly allowance with {$this->daysRemaining} days left.{$dailyCapText}",
        ];
    }
}