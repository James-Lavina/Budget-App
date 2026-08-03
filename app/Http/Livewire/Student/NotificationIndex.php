<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Notifications\DatabaseNotification;

class NotificationIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function markAsRead($notificationId)
    {
        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', auth()->id())
            ->first();

        if ($notification) {
            $notification->markAsRead();
            $this->emit('refreshNotifications'); // Sync with the bell dropdown
        }
    }

    public function markAllAsRead()
    {
        DatabaseNotification::where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->emit('refreshNotifications'); // Sync with the bell dropdown
    }

    public function render()
    {
        $notifications = DatabaseNotification::where('notifiable_id', auth()->id())
            ->latest()
            ->paginate(12);

        return view('livewire.student.notification-index', [
            'notifications' => $notifications
        ])->layout('layouts.student');
    }
}