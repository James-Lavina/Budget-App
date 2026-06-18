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
        $severity = strtoupper($this->riskLog->severity_tier);
        
        return (new MailMessage)
            ->subject("[Alert: {$severity} Risk] Budget Tracking Notification")
            ->greeting("Hello {$notifiable->name},")
            ->line("Our behavior-guided engine has detected a potential variance in your weekly budget tracking framework.")
            ->line("**Alert Details:** {$this->riskLog->description}")
            ->action('View Dashboard Analytics', route('student.dashboard'))
            ->line('Consistently reviewing your recommended daily safe-to-spend limits will help prevent early-week allowance depletion.');
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