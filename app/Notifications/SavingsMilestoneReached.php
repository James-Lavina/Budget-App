<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SavingsMilestoneReached extends Notification
{
    use Queueable;

    public $milestone;
    public $goalName;

    public function __construct($milestone, $goalName)
    {
        $this->milestone = $milestone;
        $this->goalName = $goalName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'anomaly_type' => 'savings_milestone',
            'title' => 'Savings Milestone Reached!',
            'message' => "Great job! You've officially reached {$this->milestone}% of your target for '{$this->goalName}'.",
        ];
    }
}