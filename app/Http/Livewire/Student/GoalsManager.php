<?php

namespace App\Http\Livewire\Student;

use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class GoalsManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Form & Modal State
    public $showCreateModal = false;
    public $target_name;
    public $target_amount;
    public $already_saved = 0.00;
    public $target_date;

    // Action Modals & State
    public $fundingGoalId;
    public $fund_amount;
    public $activeTab = 'active';

    // Confirmation Flags
    public $confirmingAbandonId = null;
    public $confirmingDeleteId = null;

    protected $rules = [
        'target_name' => 'required|string|max:255',
        'target_amount' => 'required|numeric|min:1|max:999999',
        'already_saved' => 'nullable|numeric|min:0',
        'target_date' => 'nullable|date|after_or_equal:today',
    ];

    protected $listeners = ['refreshSavings' => '$refresh'];

    // Reset to page 1 whenever the tab changes, so switching from a deep
    // page on "Active" to "Archived" doesn't land on a nonexistent page.
    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function storeGoal()
    {
        $this->validate();
        $initialSaved = $this->already_saved ? floatval($this->already_saved) : 0.00;
        $targetAmount = floatval($this->target_amount);
        if ($initialSaved > $targetAmount) {
            $initialSaved = $targetAmount;
        }
        $status = ($initialSaved >= $targetAmount && $targetAmount > 0) ? 'achieved' : 'active';

        $goal = SavingsGoal::create([
            'user_id' => auth()->id(),
            'target_name' => $this->target_name,
            'target_amount' => $targetAmount,
            'current_saved' => 0.00,
            'target_date' => $this->target_date ?: null,
            'status' => $status,
        ]);

        // A newly created goal lands on page 1 of the Active tab.
        $this->resetPage();

        $this->closeCreateModal();
        session()->flash('success', 'Savings milestone established successfully!');
    }

    public function openFundingModal($id)
    {
        $this->fundingGoalId = $id;
        $this->fund_amount = null;
    }

    public function addFunds()
    {
        $goal = SavingsGoal::where('id', $this->fundingGoalId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$currentBudget) {
            session()->flash('error', 'No active budget cycle found to draw funds from.');
            return;
        }

        $remainingNeeded = $goal->target_amount - $goal->current_saved;

        $this->validate([
            'fund_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:' . $currentBudget->remaining_allowance,
                'max:' . $remainingNeeded,                 
            ]
        ], [
            'fund_amount.max' => 'Transfer halted! The amount exceeds either your remaining budget (₱' . number_format($currentBudget->remaining_allowance, 2) . ') or what is left to finish this goal (₱' . number_format($remainingNeeded, 2) . ').'
        ]);

        // NOTE: previously this blanket-deleted ALL of today's RiskLog rows
        // (regardless of type/resolved status) and every low-allowance
        // notification before doing anything else. That wiped still-valid
        // warnings unrelated to this action and — same bug as LogExpense —
        // defeated the "only notify once per cycle" guard below, since the
        // guard's own exists() check always found nothing right after the
        // delete. Funding a goal can only ever push remaining_allowance
        // DOWN (money moves out to savings), never recover it, so there's
        // no "resolve on recovery" branch needed here — just don't
        // pre-emptively delete anything before evaluating.

        $goalWasAchieved = false;

        DB::transaction(function () use ($goal, $currentBudget, &$goalWasAchieved) {
            $newSavedBalance = $goal->current_saved + $this->fund_amount;
            $status = $goal->status;
            
            if ($newSavedBalance >= $goal->target_amount) {
                $status = 'achieved';
                $newSavedBalance = $goal->target_amount;
                $goalWasAchieved = true;
            }

            $goal->update([
                'current_saved' => $newSavedBalance,
                'status' => $status
            ]);

            $currentBudget->decrement('remaining_allowance', $this->fund_amount);

            $savingsCategory = ExpenseCategory::firstOrCreate(
                ['name' => 'Savings'],
                ['description' => 'Capital intentionally set aside for milestone savings targets.']
            );

            Expense::create([
                'user_id' => auth()->id(),
                'expense_category_id' => $savingsCategory->id,
                'savings_goal_id' => $goal->id,
                'item_name' => "{$goal->target_name}",
                'merchant_name' => 'Savings Goal',
                'amount' => $this->fund_amount,
                'transaction_date' => now(),
                'tracking_type' => 'manual',
            ]);
        });

        $goal->refresh();

        if ($goalWasAchieved) {
            DatabaseNotification::create([
                'id' => Str::uuid(),
                'type' => 'App\Notifications\SavingsGoalAchieved',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => auth()->id(),
                'data' => [
                    'anomaly_type' => 'goal_achieved',
                    'severity_tier' => 'success',
                    'description' => 'Target Smashed! 🎯 You successfully saved ₱' . number_format($goal->target_amount, 2) . ' for your "' . $goal->target_name . '" goal.',
                ],
                'read_at' => null,
            ]);
        } else {
            $this->checkAndNotifySavingsMilestone($goal);
        }

        app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

        $thresholdAmount = $currentBudget->total_allowance * 0.20;
        if ($currentBudget->remaining_allowance <= $thresholdAmount) {
            $alreadyNotified = DatabaseNotification::where('notifiable_id', auth()->id())
                ->where('notifiable_type', 'App\Models\User')
                ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                ->where('data', 'LIKE', '%"resolved":false%')
                ->where('created_at', '>=', $currentBudget->created_at)
                ->exists();

            if (!$alreadyNotified) {
               $percentageLeft = round(($currentBudget->remaining_allowance / $currentBudget->total_allowance) * 100);

                DatabaseNotification::create([
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\LowAllowanceWarning',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => auth()->id(),
                    'data' => [
                        'anomaly_type' => 'low_allowance_threshold',
                        'severity_tier' => 'medium',
                        'description' => "Great job saving! 🎯 Heads up: you have ₱" . number_format($currentBudget->remaining_allowance, 2) . " left for food and daily expenses this week.",
                        // NEW: matches the flag added to LowAllowanceWarning's
                        // toArray() so this manually-created notification is
                        // eligible for the same dedupe/resolve logic.
                        'resolved' => false,
                    ],
                    'read_at' => null,
                ]);
            }
        }

        $this->fundingGoalId = null;
        $this->emit('refreshNotifications');

        if ($goalWasAchieved) {
            session()->flash('success', 'Incredible! Target reached. Milestone shifted to your completed vault!');
        } else {
            session()->flash('success', 'Funds successfully transferred from your budget balance to your savings goal!');
        }
    }

    private function checkAndNotifySavingsMilestone($goal)
    {
        if ($goal->target_amount <= 0) {
            return;
        }

        $progressPercentage = round(($goal->current_saved / $goal->target_amount) * 100);
        
        $milestones = [25, 50, 75];
        $reachedMilestone = null;

        foreach ($milestones as $milestone) {
            if ($progressPercentage >= $milestone) {
                $reachedMilestone = $milestone;
            }
        }

        if (!$reachedMilestone) {
            return;
        }

        $alreadyNotified = DatabaseNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', 'App\Models\User')
            ->where('data', 'LIKE', '%"anomaly_type":"savings_milestone"%')
            ->where('data', 'LIKE', '%"milestone":' . $reachedMilestone . '%')
            ->where('data', 'LIKE', '%"goal_id":' . $goal->id . '%')
            ->exists();

        if (!$alreadyNotified) {
            DatabaseNotification::create([
                'id' => Str::uuid(),
                'type' => 'App\Notifications\SavingsMilestoneReached',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => auth()->id(),
                'data' => [
                    'anomaly_type' => 'savings_milestone',
                    'milestone' => $reachedMilestone,
                    'goal_id' => $goal->id,
                    'severity_tier' => 'success',
                    'description' => "Milestone Unlocked! 📈 You've saved {$reachedMilestone}% of your target for '{$goal->target_name}'.",
                ],
                'read_at' => null,
            ]);
        }
    }

    public function abandonGoal($id)
    {
        $this->confirmingAbandonId = $id;
    }

    public function executeAbandon()
    {
        if (!$this->confirmingAbandonId) return;

        $goal = SavingsGoal::where('id', $this->confirmingAbandonId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $goal->update(['status' => 'abandoned']);
        $this->confirmingAbandonId = null;
        session()->flash('success', 'Goal marked as archived.');
    }

    public function unarchiveGoal($id)
    {
        $goal = SavingsGoal::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $goal->update(['status' => 'active']);
        session()->flash('success', 'Savings goal successfully restored to your active dashboard!');
    }

    public function deleteGoal($id)
    {
        $this->confirmingDeleteId = $id;
    }

    public function executeDelete()
    {
        if (!$this->confirmingDeleteId) return;

        $goal = SavingsGoal::where('id', $this->confirmingDeleteId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        DB::transaction(function () use ($goal) {
            Expense::where('savings_goal_id', $goal->id)->delete();
            $goal->delete();
        });

        $this->confirmingDeleteId = null;
        session()->flash('success', 'Savings milestone and its associated transaction logs were completely cleared.');
    }

    private function resetForm()
    {
        $this->target_name = '';
        $this->target_amount = '';
        $this->target_date = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $goals = SavingsGoal::where('user_id', auth()->id())
            ->where('status', $this->activeTab)
            ->latest()
            ->paginate(6);

        $counts = [
            'active'    => SavingsGoal::where('user_id', auth()->id())->where('status', 'active')->count(),
            'achieved'  => SavingsGoal::where('user_id', auth()->id())->where('status', 'achieved')->count(),
            'abandoned' => SavingsGoal::where('user_id', auth()->id())->where('status', 'abandoned')->count(),
        ];

        return view('livewire.student.goals-manager', [
            'goals'  => $goals,
            'counts' => $counts,
        ])->layout('layouts.student');
    }
}