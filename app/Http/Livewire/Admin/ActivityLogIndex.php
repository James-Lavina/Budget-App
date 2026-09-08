<?php

namespace App\Http\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Turns the raw event_type column (e.g. 'expense_deleted') into a
     * human-readable label for the admin table. Falls back to a
     * title-cased version of the raw string for any event type not
     * explicitly mapped here, so new event types never render blank.
     */
    public function labelFor(string $eventType): string
    {
        switch ($eventType) {
            case 'expense_logged':
                return 'Logged Expense';
            case 'expense_scanned':
                return 'Scanned Receipt';
            case 'expense_deleted':
                return 'Deleted Expense';
            case 'expense_bulk_deleted':
                return 'Bulk Deleted Expenses';
            case 'expense_edited':
                return 'Edited Expense';
            case 'auth_login':
                return 'Logged In';
            case 'auth_login_failed':
                return 'Failed Login Attempt';
            case 'ocr_upload':
                return 'OCR Receipt Scan';
            case 'budget_exceeded':
                return 'Budget Exceeded';
            case 'user_edited':
                return 'Edited User';
            case 'user_suspended':
                return 'Suspended User';
            case 'user_reactivated':
                return 'Reactivated User';
            case 'user_deleted':
                return 'Deleted User';
            case 'category_created':
                return 'Created Category';
            case 'category_edited':
                return 'Edited Category';
            case 'category_disabled':
                return 'Disabled Category';
            case 'category_enabled':
                return 'Enabled Category';
            case 'category_deleted':
                return 'Deleted Category';
            case 'risk_rules_updated':
                return 'Updated Risk Rules';
            case 'ocr_ai_settings_updated':
                return 'Updated OCR & AI Settings';
            case 'app_settings_updated':
                return 'Updated Application Settings';
            case 'maintenance_mode_enabled':
                return 'Enabled Maintenance Mode';
            case 'maintenance_mode_disabled':
                return 'Disabled Maintenance Mode';
            default:
                return ucwords(str_replace('_', ' ', $eventType));
        }
    }

    /**
     * No failure state is currently logged anywhere in the app — every
     * ActivityLog row created today represents a completed action. This
     * stays as a single source of truth so if a 'failed' style event_type
     * is ever added later, only this method needs to change.
     */
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