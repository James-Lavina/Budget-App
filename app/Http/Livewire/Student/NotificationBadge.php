<?php

namespace App\Http\Livewire\Student;

use Livewire\Component;

class NotificationBadge extends Component
{
    // Listen to event emitted when notifications are marked as read
    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function render()
    {
        $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;

        return view('livewire.student.notification-badge', [
            'unreadCount' => $unreadCount
        ]);
    }
}