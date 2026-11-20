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

        if ($notifiable->wantsEmail()) {
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

    // Only added when last week used 90% or more of the budget.
    private function tip(): string
    {
        return $this->severity === 'medium'
            ? ' Last week was tight, so try to pace your spending early this week.'
            : '';
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your new budget week has started')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your weekly budget has been reset.')
            ->line($this->summary() . $this->tip())
            ->action('View Dashboard', route('student.dashboard'))
            ->line($this->severity === 'medium'
                ? 'Checking your daily limit each day makes it easier to stay on track.'
                : 'Nice work last week. Keep logging your spending to stay on track.');
    }

    public function toArray($notifiable): array
    {
        return [
            'anomaly_type'  => 'weekly_review',
            'severity_tier' => $this->severity,
            'title'         => 'Your new budget week has started',
            'description'   => $this->summary() . $this->tip(),
        ];
    }
}