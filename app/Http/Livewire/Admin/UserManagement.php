<?php

namespace App\Http\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expense;
use App\Models\RiskLog;
use App\Models\SavingsGoal;
use App\Services\BudgetCycleService;
use App\Models\ActivityLog;

class UserManagement extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

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

        // NOTE: logged with user_id => null (not the just-deleted student's ID)
        // since the FK would otherwise be dangling — the cascade above already
        // removed that user row. auth()->id() below is the admin who acted.
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
                $topGoal = SavingsGoal::where('user_id', $viewingUser->id)
                    ->where('status', 'active')
                    ->orderByDesc('target_amount')
                    ->first();

                // Simple heuristic: unresolved risk logs weighted by severity.
                // Not a formal model — gives admins a quick at-a-glance signal only.
                $weights = ['low' => 3, 'medium' => 6, 'high' => 10];
                $riskScore = RiskLog::where('user_id', $viewingUser->id)
                    ->where('resolved', false)
                    ->get()
                    ->sum(fn ($log) => $weights[$log->severity_tier] ?? 0);
                $riskScore = min(100, $riskScore);

                $budget = $viewingUser->latestWeeklyBudget;

                // FIX: total_allowance only reflects the CURRENT cycle's
                // baseline — after a weekly rollover, remaining_allowance
                // can legitimately exceed total_allowance (baseline +
                // unspent rollover from last cycle, see
                // Student\Dashboard::checkAndResetWeeklyCycle()). The old
                // formula (total - remaining) / total assumed remaining
                // could never exceed total, producing negative "% used"
                // whenever a student rolled over a large unspent balance.
                // Instead, derive actual spend from the gap between total
                // and remaining (floored at 0), then compute % used against
                // the TRUE spendable pool (remaining + spent), not just
                // the post-reset baseline.
                $budget = $viewingUser->latestWeeklyBudget;
                $spent = 0;
                $percentUsed = 0;

                if ($budget) {
                    // Derive actual spend from real expense rows inside this cycle's
                    // date window — NOT by subtracting remaining_allowance from
                    // total_allowance. total_allowance only reflects the current
                    // cycle's fresh baseline, while remaining_allowance carries over
                    // unspent rollover from last cycle (see
                    // Student\Dashboard::checkAndResetWeeklyCycle()), so remaining can
                    // legitimately exceed total right after a reset. Subtracting the
                    // two either produced a negative % (original bug) or silently
                    // floored real spend to 0 (previous "fix"), neither of which
                    // reflects what the student actually spent this week.
                    $cycle = app(BudgetCycleService::class)->resolve($budget, $viewingUser);

                    $spent = Expense::where('user_id', $viewingUser->id)
                        ->whereBetween('transaction_date', [$cycle['startDate'], $cycle['endDate']])
                        ->whereNull('savings_goal_id')
                        ->sum('amount');

                    // The true spendable pool for the cycle is whatever's left plus
                    // whatever's already gone out — this holds correctly whether or
                    // not a rollover inflated remaining_allowance above total_allowance.
                    $truePool = $budget->remaining_allowance + $spent;

                    $percentUsed = $truePool > 0
                        ? max(0, min(100, round(($spent / $truePool) * 100)))
                        : 0;
                }

                $forecastLabel = 'No Data';
                $forecastTone = 'slate';
                if ($budget) {
                    if ($budget->remaining_allowance <= 0) {
                        $forecastLabel = 'Over Budget';
                        $forecastTone = 'rose';
                    } elseif ($percentUsed >= 80) {
                        $forecastLabel = 'At Risk';
                        $forecastTone = 'amber';
                    } else {
                        $forecastLabel = 'On Track';
                        $forecastTone = 'emerald';
                    }
                }

                $viewingExtras = [
                    'topGoal' => $topGoal,
                    'riskScore' => $riskScore,
                    'percentUsed' => $percentUsed,
                    'forecastLabel' => $forecastLabel,
                    'forecastTone' => $forecastTone,
                    'recentExpenses' => Expense::where('user_id', $viewingUser->id)
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