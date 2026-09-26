<?php

namespace App\Http\Livewire\Student;

use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Models\Expense;
use App\Notifications\SavingsGoalAchieved;
use App\Services\SavingsGoalService;
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
            // FIX: this used to be hardcoded to 0.00, silently discarding
            // whatever amount the student entered as "already saved" —
            // a goal could be created with status 'achieved' but 0%
            // progress shown on screen.
            'current_saved' => $initialSaved,
            'target_date' => $this->target_date ?: null,
            'status' => $status,
        ]);

        // A goal created already at (or past) its target should tell the
        // student immediately, same as reaching it via Add Funds.
        if ($status === 'achieved') {
            try {
                auth()->user()->notify(new SavingsGoalAchieved($goal));
            } catch (\Throwable $e) {
                \Log::warning('Notification failed: ' . $e->getMessage());
            }
        } else {
            app(SavingsGoalService::class)->checkAndNotifySavingsMilestone(auth()->user(), $goal);
        }

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

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();

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

        $result = app(SavingsGoalService::class)->addFunds(auth()->user(), $goal, (float) $this->fund_amount);

        $this->fundingGoalId = null;
        $this->emit('refreshNotifications');

        session()->flash('success', $result['goalWasAchieved']
            ? 'Incredible! Target reached. Milestone shifted to your completed vault!'
            : 'Funds successfully transferred from your budget balance to your savings goal!');
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($goal) {
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