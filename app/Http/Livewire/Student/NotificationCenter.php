<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use Illuminate\Notifications\DatabaseNotification;

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
    }

    public function markAllAsRead()
    {
        DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * NEW: dismiss a single notification from the dropdown without
     * navigating to the full Notification Center page.
     */
    public function dismiss($notificationId)
    {
        DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', auth()->id())
            ->delete();

        $this->emit('refreshNotifications');
    }

    public function render()
    {
        // NEW: cap to 6 most recent unread — prevents the dropdown from
        // growing unbounded for users who don't check it often.
        $notifications = DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->latest()
            ->take(6)
            ->get();

        $totalUnreadCount = DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return view('livewire.student.notification-center', [
            'notifications'     => $notifications,
            'totalUnreadCount'  => $totalUnreadCount,
        ]);
    }
}