<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowAllowanceWarning extends Notification
{
    use Queueable;

    public $percentageLeft;
    public $remainingAllowance;

    public function __construct($percentageLeft, $remainingAllowance)
    {
        $this->percentageLeft = $percentageLeft;
        $this->remainingAllowance = $remainingAllowance;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'anomaly_type' => 'low_allowance_threshold',
            'severity_tier' => 'medium',
            'description' => "Low Allowance Warning ⚠️: Your remaining balance has dropped to {$this->percentageLeft}% (₱" . number_format($this->remainingAllowance, 2) . " left). Pace your spending to comfortably finish the week!",
            // NEW: flipped to true once the balance recovers above the
            // threshold, instead of deleting the notification wholesale.
            'resolved' => false,
        ];
    }
}