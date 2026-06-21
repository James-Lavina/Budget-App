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

class GoalsManager extends Component
{
    public $target_name;
    public $target_amount;
    public $target_date;
    
    public $fundingGoalId;
    public $fund_amount;
    public $activeTab = 'active'; 

    // Confirmation Flags
    public $confirmingAbandonId = null;
    public $confirmingDeleteId = null;

    protected $rules = [
        'target_name' => 'required|string|max:255',
        'target_amount' => 'required|numeric|min:1|max:999999',
        'target_date' => 'nullable|date|after_or_equal:today',
    ];

    protected $listeners = ['refreshSavings' => '$refresh'];

    public function storeGoal()
    {
        $this->validate();

        SavingsGoal::create([
            'user_id' => auth()->id(),
            'target_name' => $this->target_name,
            'target_amount' => $this->target_amount,
            'current_saved' => 0.00,
            'target_date' => $this->target_date ?: null,
            'status' => 'active',
        ]);

        $this->resetForm();
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

        RiskLog::where('user_id', auth()->id())
            ->whereDate('created_at', Carbon::today())
            ->delete();

        DatabaseNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', 'App\Models\User')
            ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
            ->delete();

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
        }

        app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

        $thresholdAmount = $currentBudget->total_allowance * 0.20;
        if ($currentBudget->remaining_allowance <= $thresholdAmount) {
            
            $alreadyNotified = DatabaseNotification::where('notifiable_id', auth()->id())
                ->where('notifiable_type', 'App\Models\User')
                ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
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
                        'description' => "Budget Critical! ⚠️ Your remaining allowance has dropped to {$percentageLeft}% (₱" . number_format($currentBudget->remaining_allowance, 2) . " left). Consider lowering your daily velocity to survive the cycle.",
                    ],
                    'read_at' => null,
                ]);
            }
        }

        $this->fundingGoalId = null;

        if ($goalWasAchieved) {
            session()->flash('success', 'Incredible! Target reached. Milestone shifted to your completed vault!');
        } else {
            session()->flash('success', 'Funds successfully transferred from your budget balance to your savings goal!');
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

        // Safe relational cascading execution block
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
    }

    public function render()
    {
        $goals = SavingsGoal::where('user_id', auth()->id())
            ->where('status', $this->activeTab)
            ->latest()
            ->get();

        return view('livewire.student.goals-manager', [
            'goals' => $goals
        ])->layout('layouts.student');
    }
}