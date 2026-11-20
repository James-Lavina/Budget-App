<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SavingsGoalAchieved extends Notification
{
    use Queueable;

    protected $goal;

    public function __construct($goal)
    {
        $this->goal = $goal;
    }

    public function via($notifiable)
    {
        $channels = ['database'];

        if ($notifiable->wantsEmail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        $goalName     = $this->goal->target_name ?? 'Savings Goal';
        $targetAmount = $this->goal->target_amount ?? 0;

        return (new MailMessage)
            ->subject('Goal reached: ' . $goalName)
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('You reached 100% of your savings goal "' . $goalName . '".')
            ->line('Amount saved: ₱' . number_format($targetAmount, 2))
            ->action('View my savings goals', route('student.goals'))
            ->line('Nice work. Setting a new goal is a great way to keep the habit going.');
    }

    public function toArray($notifiable)
    {
        $goalName     = $this->goal->target_name ?? 'Savings Goal';
        $targetAmount = $this->goal->target_amount ?? 0;

        return [
            'anomaly_type'  => 'goal_achieved',
            'severity_tier' => 'success',
            'title'         => 'Goal reached!',
            'description'   => 'You saved ₱' . number_format($targetAmount, 2) . ' for "' . $goalName . '". Well done!',
        ];
    }
}