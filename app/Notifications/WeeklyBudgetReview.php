<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WeeklyBudgetReview extends Notification
{
    use Queueable;

    protected float $amountSpent;
    protected float $unspentSavings;
    protected string $severity;

    public function __construct(float $amountSpent, float $unspentSavings, string $severity = 'low')
    {
        $this->amountSpent = $amountSpent;
        $this->unspentSavings = $unspentSavings;
        $this->severity = $severity;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $spentFormatted = number_format($this->amountSpent, 2);
        $savingsFormatted = number_format($this->unspentSavings, 2);

        return (new MailMessage)
            ->subject('🎉 Weekly Budget Reset & Rollover Summary')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your weekly budget cycle has officially reset.")
            ->line("Last week you spent ₱{$spentFormatted} and successfully rolled over ₱{$savingsFormatted} into your new budget.")
            ->action('View Dashboard', route('student.dashboard'))
            ->line('Keep up the great financial discipline!');
    }

    public function toArray($notifiable): array
    {
        $spentFormatted = number_format($this->amountSpent, 2);
        $savingsFormatted = number_format($this->unspentSavings, 2);

        return [
            'anomaly_type'  => 'weekly_review',
            'severity_tier' => $this->severity,
            'description'   => "🎉 New Week, Fresh Start! Last week you spent ₱{$spentFormatted} and successfully rolled over ₱{$savingsFormatted} into your new budget.",
        ];
    }
}