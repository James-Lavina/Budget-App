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
        $this->percentageLeft     = $percentageLeft;
        $this->remainingAllowance = $remainingAllowance;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'anomaly_type'  => 'low_allowance_threshold',
            'severity_tier' => 'medium',
            'title'         => 'Your budget is running low',
            'description'   => "Only {$this->percentageLeft}% of your allowance is left (₱"
                . number_format($this->remainingAllowance, 2)
                . '). Spread it out so it lasts until your next reset.',
            // Flipped to true once the balance recovers above the threshold.
            'resolved'      => false,
        ];
    }
}