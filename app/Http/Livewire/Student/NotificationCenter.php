<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use Illuminate\Notifications\DatabaseNotification; // Import Laravel's notification model

class NotificationCenter extends Component
{
    protected $listeners = ['refreshNotifications' => '$refresh'];

    /**
     * Marks a single notification as read safely
     */
    public function markAsRead($notificationId)
    {
        // Target the notification directly while ensuring it belongs to the logged-in student
        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', auth()->id())
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }
    }

    /**
     * Marks all unread notifications for this student as read at once
     */
    public function markAllAsRead()
    {
        DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        // Pull unread notifications directly from the database table
        $notifications = DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->latest()
            ->get();

        return view('livewire.student.notification-center', [
            'notifications' => $notifications
        ]);
    }
}