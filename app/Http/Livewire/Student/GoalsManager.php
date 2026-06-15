<?php

namespace App\Http\Livewire\Student;

use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GoalsManager extends Component
{
    public $target_name;
    public $target_amount;
    public $target_date;
    
    public $fundingGoalId;
    public $fund_amount;
    public $activeTab = 'active'; 

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

        // 1. Fetch the active budget cycle
        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest() 
            ->first();

        if (!$currentBudget) {
            session()->flash('error', 'No active budget cycle found to draw funds from.');
            return;
        }

        // Calculate exactly how much is left to complete the goal
        $remainingNeeded = $goal->target_amount - $goal->current_saved;

        // 2. Strict Dynamic Validation
        $this->validate([
            'fund_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:' . $currentBudget->remaining_allowance, 
                'max:' . $remainingNeeded,                    
            ]
        ], [
            'fund_amount.max' => 'Injection halted! The amount exceeds either your remaining budget (₱' . number_format($currentBudget->remaining_allowance, 2) . ') or what is left to finish this goal (₱' . number_format($remainingNeeded, 2) . ').'
        ]);

        // 3. Database Transaction to sync all components safely
        DB::transaction(function () use ($goal, $currentBudget) {
            $newSavedBalance = $goal->current_saved + $this->fund_amount;
            $status = $goal->status;
            
            if ($newSavedBalance >= $goal->target_amount) {
                $status = 'achieved';
                $newSavedBalance = $goal->target_amount; 
            }

            // A. Update the Savings Goal record
            $goal->update([
                'current_saved' => $newSavedBalance,
                'status' => $status
            ]);

            // B. Subtract the amount from your actual wallet budget allowance
            $currentBudget->decrement('remaining_allowance', $this->fund_amount);

            // C. Fetch or generate the 'Savings' type category to satisfy foreign key constraint
            $savingsCategory = \App\Models\ExpenseCategory::firstOrCreate(
                ['name' => 'Savings'],
                ['description' => 'Capital intentionally set aside for milestone savings targets.']
            );

            // D. Log it as an official Expense transaction mapped to the category
            \App\Models\Expense::create([
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

        $this->fundingGoalId = null;
        session()->flash('success', 'Funds successfully transferred from your budget balance to your savings goal!');
    }

    public function abandonGoal($id)
    {
        $goal = SavingsGoal::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $goal->update(['status' => 'abandoned']);
        session()->flash('success', 'Goal marked as archived.');
    }

    public function deleteGoal($id)
    {
        $goal = SavingsGoal::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $goal->delete();
        session()->flash('success', 'Savings target permanently removed.');
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