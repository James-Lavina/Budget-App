<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LargeTransactionAlert extends Notification
{
    use Queueable;

    public $expenseId;
    public $itemName;
    public $amount;
    public $percentage;

    public function __construct($expenseId, $itemName, $amount, $percentage)
    {
        $this->expenseId = $expenseId;
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
            'expense_id'    => $this->expenseId,
            'description'   => "Big Purchase Flagged 💸: \"{$this->itemName}\" used ₱" . number_format($this->amount, 2) . " ({$this->percentage}% of your weekly allowance) in one go.",
            'resolved'      => false,
        ];
    }
}