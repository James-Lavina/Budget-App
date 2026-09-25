<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SavingsMilestoneReached extends Notification
{
    use Queueable;

    public $milestone;
    public $goalName;
    public $goalId;

    public function __construct($milestone, $goalName, $goalId)
    {
        $this->milestone = $milestone;
        $this->goalName  = $goalName;
        $this->goalId    = $goalId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'anomaly_type'  => 'savings_milestone',
            'milestone'     => $this->milestone,
            'goal_id'       => $this->goalId,
            'severity_tier' => 'success',
            'description'   => "Milestone Unlocked! 📈 You've saved {$this->milestone}% of your target for '{$this->goalName}'.",
        ];
    }
}