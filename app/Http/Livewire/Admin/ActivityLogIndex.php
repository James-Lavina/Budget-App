<?php

namespace App\Http\Livewire\Admin;

use App\Http\Livewire\Concerns\EnsuresAdmin;
use App\Models\ActivityLog;
use App\Support\ActivityLabels;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogIndex extends Component
{
    use WithPagination, EnsuresAdmin;

    protected $paginationTheme = 'tailwind';

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function labelFor(string $eventType): string
    {
        return ActivityLabels::for($eventType);
    }

    public function statusFor(string $eventType): array
    {
        if (strpos($eventType, 'failed') !== false) {
            return ['label' => 'Failed', 'class' => 'bg-rose-50 text-rose-600'];
        }

        return ['label' => 'Success', 'class' => 'bg-emerald-50 text-emerald-600'];
    }

    public function render()
    {
        $logs = ActivityLog::with('user')
            ->when(filled($this->search), function ($query) {
                $term = trim($this->search);

                $query->where(function ($sub) use ($term) {
                    $sub->where('details', 'like', "%{$term}%")
                        ->orWhere('event_type', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery->where('name', 'like', "%{$term}%");
                        });
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.activity-log-index', [
            'logs' => $logs,
        ])->layout('layouts.admin');
    }
}