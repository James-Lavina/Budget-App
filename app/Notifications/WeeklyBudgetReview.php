<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyBudgetReview extends Notification
{
    use Queueable;

    protected float $amountSpent;
    protected float $unspentSavings;
    protected string $severity;
    protected float $amountSaved;

    public function __construct(float $amountSpent, float $unspentSavings, string $severity = 'success', float $amountSaved = 0.0)
    {
        $this->amountSpent    = $amountSpent;
        $this->unspentSavings = $unspentSavings;
        $this->severity       = $severity;
        $this->amountSaved    = $amountSaved;
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        if (\App\Models\AppSetting::current()->email_notifications_enabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    private function summary(): string
    {
        $text = 'Last week you spent ₱' . number_format($this->amountSpent, 2);

        if ($this->amountSaved > 0) {
            $text .= ', saved ₱' . number_format($this->amountSaved, 2);
        }

        return $text . ' and rolled over ₱' . number_format($this->unspentSavings, 2) . ' into your new budget.';
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Weekly Budget Reset & Rollover Summary')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your weekly budget cycle has officially reset.')
            ->line($this->summary())
            ->action('View Dashboard', route('student.dashboard'))
            ->line('Keep up the great financial discipline!');
    }

    public function toArray($notifiable): array
    {
        return [
            'anomaly_type'  => 'weekly_review',
            'severity_tier' => $this->severity,
            'description'   => '🎉 New Week, Fresh Start! ' . $this->summary(),
        ];
    }
}