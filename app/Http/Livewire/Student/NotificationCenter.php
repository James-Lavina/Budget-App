<?php

namespace App\Http\Livewire\Student;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationCenter extends Component
{
    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function markAsRead($notificationId)
    {
        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', auth()->id())
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        $this->emit('refreshNotifications');
    }

    public function markAllAsRead()
    {
        DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->emit('refreshNotifications');
    }

    // Hides it from the dropdown but keeps the row, which alert dedupe depends on.
    public function dismiss($notificationId)
    {
        $this->markAsRead($notificationId);
    }

    public function render()
    {
        $notifications = DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->latest()
            ->take(6)
            ->get();

        $totalUnreadCount = DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return view('livewire.student.notification-center', [
            'notifications'    => $notifications,
            'totalUnreadCount' => $totalUnreadCount,
        ]);
    }
}