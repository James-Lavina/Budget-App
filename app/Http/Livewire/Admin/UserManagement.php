<?php

namespace App\Http\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\RiskLog;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Services\BudgetCycleService;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /**
     * Notification types that are alerts but never write a risk_logs row.
     * BudgetRiskNotification is deliberately excluded: it is always created
     * from a RiskLog, so counting it here would double count the same alert.
     * Success-type notifications (goal achieved, milestone, weekly review)
     * are not alerts and are also excluded.
     */
    private const NOTIFICATION_ALERT_TYPES = [
        'low_allowance_threshold',
        'category_concentration',
        'large_transaction',
        'no_expense_logs',
    ];

    public $search = '';
    public $statusFilter = '';

    public $viewingUserId = null;

    public $editingUserId = null;
    public $edit_name;
    public $edit_email;
    public $edit_school;
    public $edit_default_allowance;
    public $edit_default_reset_day;

    public $confirmingSuspendId = null;
    public $confirmingDeleteId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    // --- View ---
    public function viewUser($id)
    {
        $this->viewingUserId = $id;
    }

    public function closeView()
    {
        $this->viewingUserId = null;
    }

    // --- Edit ---
    public function openEdit($id)
    {
        $user = User::where('role', 'student')->findOrFail($id);
        $this->editingUserId = $user->id;
        $this->edit_name = $user->name;
        $this->edit_email = $user->email;
        $this->edit_school = $user->school;
        $this->edit_default_allowance = $user->default_allowance;
        $this->edit_default_reset_day = $user->default_reset_day ?? 'Monday';
        $this->resetErrorBag();
    }

    public function closeEdit()
    {
        $this->editingUserId = null;
    }

    public function saveEdit()
    {
        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_email' => 'required|email|max:255|unique:users,email,' . $this->editingUserId,
            'edit_school' => 'nullable|string|max:255',
            'edit_default_allowance' => 'required|numeric|min:1|max:999999',
            'edit_default_reset_day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        ]);

        $user = User::where('role', 'student')->findOrFail($this->editingUserId);

        $changes = [];
        if ($user->name !== $this->edit_name) {
            $changes[] = "name: \"{$user->name}\" → \"{$this->edit_name}\"";
        }
        if ($user->email !== $this->edit_email) {
            $changes[] = "email: \"{$user->email}\" → \"{$this->edit_email}\"";
        }
        if ($user->school !== $this->edit_school) {
            $changes[] = "school: \"" . ($user->school ?? '—') . "\" → \"" . ($this->edit_school ?? '—') . "\"";
        }
        if ((float) $user->default_allowance !== (float) $this->edit_default_allowance) {
            $changes[] = "default allowance: ₱" . number_format($user->default_allowance, 2) . " → ₱" . number_format($this->edit_default_allowance, 2);
        }
        if ($user->default_reset_day !== $this->edit_default_reset_day) {
            $changes[] = "reset day: {$user->default_reset_day} → {$this->edit_default_reset_day}";
        }

        $user->update([
            'name' => $this->edit_name,
            'email' => $this->edit_email,
            'school' => $this->edit_school,
            'default_allowance' => $this->edit_default_allowance,
            'default_reset_day' => $this->edit_default_reset_day,
        ]);

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'user_edited',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Edited student \"{$user->name}\" (ID {$user->id})" . (!empty($changes) ? ': ' . implode(', ', $changes) : ' — no field changes detected'),
        ]);

        $this->editingUserId = null;
        session()->flash('success', "{$user->name}'s profile was updated.");
    }

    // --- Suspend / Reactivate ---
    public function confirmSuspend($id)
    {
        $this->confirmingSuspendId = $id;
    }

    public function cancelSuspend()
    {
        $this->confirmingSuspendId = null;
    }

    public function toggleSuspend()
    {
        if (!$this->confirmingSuspendId) {
            return;
        }

        $user = User::where('role', 'student')->findOrFail($this->confirmingSuspendId);
        $wasActive = $user->status !== 'suspended';

        $user->status = $wasActive ? 'suspended' : 'active';
        $user->save();

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => $wasActive ? 'user_suspended' : 'user_reactivated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => ($wasActive ? 'Suspended' : 'Reactivated') . " student \"{$user->name}\" (ID {$user->id}, {$user->email})",
        ]);

        $this->confirmingSuspendId = null;
        session()->flash('success', $user->status === 'suspended'
            ? "{$user->name} has been suspended."
            : "{$user->name} has been reactivated.");
    }

    // --- Delete ---
    public function confirmDelete($id)
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete()
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteUser()
    {
        if (!$this->confirmingDeleteId) {
            return;
        }

        $user = User::where('role', 'student')->findOrFail($this->confirmingDeleteId);
        $name = $user->name;
        $email = $user->email;
        $userId = $user->id;

        $user->delete(); // cascades to expenses/budgets/goals/logs via FK constraints

        // The activity is logged against the admin who acted (auth()->id()),
        // not the deleted student, since that user row no longer exists.
        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'user_deleted',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => "Permanently deleted student \"{$name}\" (ID {$userId}, {$email}) and all associated records",
        ]);

        $this->confirmingDeleteId = null;
        session()->flash('success', "{$name} and all associated records were permanently deleted.");
    }

    /**
     * Active alerts for the current cycle, combining both alert sources:
     *  1. Unresolved risk_logs (pace check, overspending threshold,
     *     daily safe-to-spend, rapid spending).
     *  2. Unresolved notification-only alerts (low allowance, category
     *     concentration, large transaction, no expense logs).
     *
     * Only alerts created since the cycle start are counted, so old
     * never-resolved rows from previous weeks don't inflate the number.
     * Note: a notification the student deleted no longer counts.
     */
    private function activeAlertsFor(User $user, $cycle): array
    {
        $riskQuery = RiskLog::where('user_id', $user->id)->where('resolved', false);

        $notifQuery = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\Models\User');

        if ($cycle) {
            $riskQuery->where('created_at', '>=', $cycle['startDate']);
            $notifQuery->where('created_at', '>=', $cycle['startDate']);
        }

        $riskTiers = $riskQuery->pluck('severity_tier');

        $notifTiers = $notifQuery->get()
            ->filter(function ($notification) {
                $data = $notification->data;

                return in_array($data['anomaly_type'] ?? null, self::NOTIFICATION_ALERT_TYPES, true)
                    && !($data['resolved'] ?? false);
            })
            ->map(function ($notification) {
                return $notification->data['severity_tier'] ?? 'medium';
            });

        $tiers = $riskTiers->concat($notifTiers);

        $high   = $tiers->filter(fn ($t) => $t === 'high')->count();
        $medium = $tiers->filter(fn ($t) => $t === 'medium')->count();
        $low    = $tiers->filter(fn ($t) => $t === 'low')->count();
        $total  = $tiers->count();

        $parts = [];
        if ($high > 0) {
            $parts[] = "{$high} high";
        }
        if ($medium > 0) {
            $parts[] = "{$medium} medium";
        }
        if ($low > 0) {
            $parts[] = "{$low} low";
        }

        if ($high > 0) {
            $tone = 'rose';
        } elseif ($total > 0) {
            $tone = 'amber';
        } else {
            $tone = 'emerald';
        }

        return [
            'total'   => $total,
            'summary' => $total > 0 ? implode(' · ', $parts) : 'All clear',
            'tone'    => $tone,
        ];
    }

    /**
     * Pace-based forecast, mirroring the student Dashboard's ranked state
     * (depleted -> pace critical -> fresh start -> on track) so the admin
     * and the student never see contradicting labels.
     */
    private function forecastFor(User $user, $budget, $cycle): array
    {
        if (!$budget || !$cycle) {
            return [
                'label' => 'No Data',
                'tone'  => 'slate',
                'note'  => 'This student has not set up a budget yet.',
            ];
        }

        $remaining     = (float) $budget->remaining_allowance;
        $daysElapsed   = max(1, (int) $cycle['daysElapsed']);
        $daysRemaining = max(1, (int) $cycle['daysRemaining']);
        $isFinalDay    = $daysElapsed >= 7;

        // Same spend definition as the student Dashboard: up to the evaluation
        // date, excluding savings-goal transfers and the Savings category.
        $totalSpent = (float) Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycle['startDate'], $cycle['evalDate']->copy()->endOfDay()])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $dailyVelocity     = $totalSpent / $daysElapsed;
        $projectedDaysLeft = $dailyVelocity > 0 ? ($remaining / $dailyVelocity) : $daysRemaining;

        if ($remaining <= 0) {
            return [
                'label' => 'Budget Exhausted',
                'tone'  => 'rose',
                'note'  => 'No allowance left until the next reset.',
            ];
        }

        if (!$isFinalDay && $projectedDaysLeft < $daysRemaining) {
            return [
                'label' => 'Spending Warning',
                'tone'  => 'rose',
                'note'  => 'At ₱' . number_format($dailyVelocity, 2) . '/day, the remaining balance lasts about '
                    . number_format($projectedDaysLeft, 1) . ' of the ' . $daysRemaining . ' day(s) left.',
            ];
        }

        if ($totalSpent <= 0) {
            return [
                'label' => 'Fresh Start',
                'tone'  => 'slate',
                'note'  => 'No spending logged yet this cycle.',
            ];
        }

        return [
            'label' => 'On Track',
            'tone'  => 'emerald',
            'note'  => 'Averaging ₱' . number_format($dailyVelocity, 2) . '/day with ' . $daysRemaining . ' day(s) left.',
        ];
    }

    public function render()
    {
        $users = User::where('role', 'student')
            ->with('latestWeeklyBudget')
            ->when(filled($this->search), function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('school', 'like', $term);
                });
            })
            ->when(filled($this->statusFilter), function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(10);

        $viewingUser = null;
        $viewingExtras = null;

        if ($this->viewingUserId) {
            $viewingUser = User::with('latestWeeklyBudget')->find($this->viewingUserId);

            if ($viewingUser) {
                $budget = $viewingUser->latestWeeklyBudget;

                // FIX: readOnly = true — an admin opening this modal was
                // previously able to trigger the STUDENT's weekly reset
                // just by viewing their profile, since resolve() now
                // persists a rollover as a side effect. Admin views must
                // only inspect the cycle, never mutate it on the
                // student's behalf.
                $cycle = $budget ? app(BudgetCycleService::class)->resolve($budget, $viewingUser, true) : null;

                $topGoal = SavingsGoal::where('user_id', $viewingUser->id)
                    ->where('status', 'active')
                    ->orderByDesc('target_amount')
                    ->first();

                // Effective allowance = base + rollover, the same figure the
                // student sees as "Total Available This Week". total_allowance
                // alone excludes rollover and can be lower than remaining.
                $effectiveAllowance = $cycle
                    ? (float) $cycle['effectiveTotalAllowance']
                    : (float) ($viewingUser->default_allowance ?? 0);

                // Same formula as the student Dashboard's "% used".
                $percentUsed = 0;
                if ($budget && $effectiveAllowance > 0) {
                    $percentUsed = (int) max(0, min(100, round(
                        (1 - ((float) $budget->remaining_allowance / $effectiveAllowance)) * 100
                    )));
                }

                $forecast = $this->forecastFor($viewingUser, $budget, $cycle);
                $alerts = $this->activeAlertsFor($viewingUser, $cycle);

                $viewingExtras = [
                    'topGoal'            => $topGoal,
                    'effectiveAllowance' => $effectiveAllowance,
                    'percentUsed'        => $percentUsed,
                    'forecastLabel'      => $forecast['label'],
                    'forecastTone'       => $forecast['tone'],
                    'forecastNote'       => $forecast['note'],
                    'alerts'             => $alerts,
                    'recentExpenses'     => Expense::where('user_id', $viewingUser->id)
                        ->latest('transaction_date')
                        ->take(3)
                        ->get(),
                ];
            }
        }

        return view('livewire.admin.user-management', [
            'users' => $users,
            'viewingUser' => $viewingUser,
            'viewingExtras' => $viewingExtras,
        ])->layout('layouts.admin');
    }
}