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

    /**
     * Pass the generated RiskLog instance into the notification context
     */
    public function __construct(RiskLog $riskLog)
    {
        $this->riskLog = $riskLog;
    }

    /**
     * Determine which channels the notification will use.
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Compile the Email Payload
     */
    public function toMail($notifiable)
    {
        $severity = ucfirst(strtolower($this->riskLog->severity_tier));

        return (new MailMessage)
            ->subject("[Pace Check: {$severity} Priority] Weekly Budget Update")
            ->greeting("Hello {$notifiable->name},")
            ->line("Heads up! We noticed your spending pace is running a bit faster than planned for this cycle.")
            ->line("**Alert Details:** {$this->riskLog->description}")
            ->action('View Dashboard Analytics', route('student.dashboard'))
            ->line('Keeping an eye on your daily spending cap helps make sure your allowance lasts comfortably until the end of the week.');
    }

    /**
     * Compile the Database/In-App Payload (Stored as JSON)
     */
    public function toArray($notifiable)
    {
        return [
            'risk_log_id'   => $this->riskLog->id,
            'anomaly_type'  => $this->riskLog->anomaly_type,
            'severity_tier' => $this->riskLog->severity_tier,
            'description'   => $this->riskLog->description,
        ];
    }
}