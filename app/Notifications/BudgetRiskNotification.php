<?php

namespace App\Notifications;

use App\Models\RiskLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetRiskNotification extends Notification
{
    use Queueable;

    // anomaly_type => [title, email intro]
    public const COPY = [
        'early_week_depletion'   => ["You're spending faster than planned", 'Your spending pace is faster than planned for this week.'],
        'rapid_overspending'     => ["You're spending faster than planned", 'Your spending pace is faster than planned for this week.'],
        'overspending_threshold' => ["Most of this week's allowance is used", "You've used most of this week's allowance."],
        'daily_safe_to_spend'    => ["You're close to today's limit", "You're close to today's safe-to-spend amount."],
        'rapid_spending'         => ['Several big purchases today', "You've made several large purchases today."],
    ];

    public const FALLBACK = ['Budget update', 'We noticed something in your spending.'];

    public $riskLog;

    public function __construct(RiskLog $riskLog)
    {
        $this->riskLog = $riskLog;
    }

    public static function titleFor(string $type): string
    {
        return (self::COPY[$type] ?? self::FALLBACK)[0];
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
        [$title, $intro] = self::COPY[$this->riskLog->anomaly_type] ?? self::FALLBACK;

        $subject = strtolower($this->riskLog->severity_tier) === 'high'
            ? "Urgent: {$title}"
            : $title;

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line($intro)
            ->line($this->riskLog->description)
            ->action('Open my dashboard', route('student.dashboard'))
            ->line('Checking your daily limit on the dashboard helps your allowance last until reset day.');
    }

    public function toArray($notifiable)
    {
        return [
            'risk_log_id'   => $this->riskLog->id,
            'anomaly_type'  => $this->riskLog->anomaly_type,
            'severity_tier' => $this->riskLog->severity_tier,
            'title'         => self::titleFor($this->riskLog->anomaly_type),
            'description'   => $this->riskLog->description,
            'resolved'      => false,
        ];
    }
}