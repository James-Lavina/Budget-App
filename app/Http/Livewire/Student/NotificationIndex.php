<?php

namespace App\Http\Livewire\Student;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Alerts that RiskDetectionService re-creates on the next evaluation if the row is missing.
    public const RESURFACING_ALERTS = [
        'early_week_depletion',
        'rapid_overspending',
        'overspending_threshold',
        'daily_safe_to_spend',
        'rapid_spending',
        'low_allowance_threshold',
        'category_concentration',
        'no_expense_logs',
    ];

    public function markAsRead($notificationId)
    {
        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', auth()->id())
            ->first();

        if ($notification) {
            $notification->markAsRead();
            $this->emit('refreshNotifications');
        }
    }

    public function markAllAsRead()
    {
        DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->emit('refreshNotifications');
    }

    public function delete($notificationId)
    {
        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', auth()->id())
            ->first();

        if (!$notification) {
            return;
        }

        $data        = $notification->data;
        $stillActive = in_array($data['anomaly_type'] ?? null, self::RESURFACING_ALERTS, true)
            && !($data['resolved'] ?? false);

        if ($stillActive) {
            $notification->markAsRead();
            session()->flash('error', "That alert is still active, so it can't be deleted. It was marked as read and will clear once you're back on track.");
        } else {
            $notification->delete();
        }

        $this->emit('refreshNotifications');
    }

    public function render()
    {
        $notifications = DatabaseNotification::where('notifiable_id', auth()->id())
            ->latest()
            ->paginate(12);

        return view('livewire.student.notification-index', [
            'notifications' => $notifications,
            'unreadCount'   => DatabaseNotification::where('notifiable_id', auth()->id())->whereNull('read_at')->count(),
        ])->layout('layouts.student');
    }
}