<?php

namespace App\Notifications;

use App\Models\RiskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetRiskNotification extends Notification
{
    use Queueable;

    public $riskLog;

    public function __construct(RiskLog $riskLog)
    {
        $this->riskLog = $riskLog;
    }

    public function via($notifiable)
    {
        $channels = ['database'];

        if (\App\Models\AppSetting::current()->email_notifications_enabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        $severity  = ucfirst(strtolower($this->riskLog->severity_tier));
        $paceIntro = 'Your spending pace is running faster than planned for this cycle.';

        $copy = [
            'early_week_depletion'   => ['Pace Check', $paceIntro],
            'rapid_overspending'     => ['Pace Check', $paceIntro],
            'overspending_threshold' => ['Budget Alert', "You've used most of this week's allowance."],
            'daily_safe_to_spend'    => ['Daily Limit', "You're close to today's safe-to-spend amount."],
            'rapid_spending'         => ['Rapid Spending', "You've made several large purchases today."],
        ];

        [$label, $intro] = $copy[$this->riskLog->anomaly_type] ?? ['Budget Update', 'We noticed something in your spending.'];

        return (new MailMessage)
            ->subject("[{$label}: {$severity} Priority] Budget Alert")
            ->greeting("Hello {$notifiable->name},")
            ->line($intro)
            ->line("**Alert Details:** {$this->riskLog->description}")
            ->action('View Dashboard Analytics', route('student.dashboard'))
            ->line('Keeping an eye on your daily spending cap helps make sure your allowance lasts until reset day.');
    }

    public function toArray($notifiable)
    {
        return [
            'risk_log_id'   => $this->riskLog->id,
            'anomaly_type'  => $this->riskLog->anomaly_type,
            'severity_tier' => $this->riskLog->severity_tier,
            'description'   => $this->riskLog->description,
            'resolved'      => false,
        ];
    }
}