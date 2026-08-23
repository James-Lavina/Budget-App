<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LargeTransactionAlert extends Notification
{
    use Queueable;

    public $itemName;
    public $amount;
    public $percentage;

    public function __construct($itemName, $amount, $percentage)
    {
        $this->itemName = $itemName;
        $this->amount = $amount;
        $this->percentage = $percentage;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'anomaly_type'  => 'large_transaction',
            'severity_tier' => 'medium',
            'description'   => "Big Purchase Flagged 💸: \"{$this->itemName}\" used ₱" . number_format($this->amount, 2) . " ({$this->percentage}% of your weekly allowance) in one go.",
        ];
    }
}