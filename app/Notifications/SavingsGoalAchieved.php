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
        return (new MailMessage)
            ->subject('🎯 Goal Smashed: ' . $this->goal->name)
            ->greeting('Awesome job, ' . $notifiable->name . '!')
            ->line('Your financial discipline just pushed your savings goal "' . $this->goal->name . '" to 100% completion!')
            ->line('Total Target Saved: ₱' . number_format($this->goal->target_amount, 2))
            ->action('View Savings Vault', url('/dashboard/savings'))
            ->line('Keep up this incredible financial habit!');
    }

    // Structure the JSON payload that goes into your notifications table
    public function toArray($notifiable)
{
    return [
        'anomaly_type' => 'goal_achieved',
        'severity_tier' => 'success',
        // Make sure this uses target_name and target_amount:
        'description' => 'Target Smashed! 🎯 You successfully saved ₱' . number_format($this->goal->target_amount, 2) . ' for your "' . $this->goal->target_name . '" goal.',
    ];
}
}