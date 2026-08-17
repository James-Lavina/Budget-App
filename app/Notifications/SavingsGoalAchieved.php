<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SavingsGoalAchieved extends Notification
{
    use Queueable;

    protected $goal;

    // Pass the savings goal model into the notification constructor
    public function __construct($goal)
    {
        $this->goal = $goal;
    }

    // Define the channels this notification sends to
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    // Package the HTML/Log email structure
    public function toMail($notifiable)
    {
        $goalName = $this->goal->name ?? $this->goal->target_name ?? 'Savings Goal';
        $targetAmount = $this->goal->target_amount ?? 0;

        return (new MailMessage)
            ->subject('🎯 Goal Smashed: ' . $goalName)
            ->greeting('Awesome job, ' . $notifiable->name . '!')
            ->line('Your financial discipline just pushed your savings goal "' . $goalName . '" to 100% completion!')
            ->line('Total Target Saved: ₱' . number_format($targetAmount, 2))
            ->action('View Savings Vault', url('/dashboard/savings'))
            ->line('Keep up this incredible financial habit!');
    }

    // Structure the JSON payload that goes into your notifications table
    public function toArray($notifiable)
    {
        $goalName = $this->goal->target_name ?? $this->goal->name ?? 'Savings Goal';
        $targetAmount = $this->goal->target_amount ?? 0;

        return [
            'anomaly_type'  => 'goal_achieved',
            'severity_tier' => 'success',
            'description'   => 'Target Smashed! 🎯 You successfully saved ₱' . number_format($targetAmount, 2) . ' for your "' . $goalName . '" goal.',
        ];
    }
}