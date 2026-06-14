<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AllExpenses extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function deleteExpense($expenseId)
    {
        $expense = Expense::where('id', $expenseId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$expense) {
            session()->flash('error', 'Expense record not found.');
            return;
        }

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($currentBudget) {
            DB::transaction(function () use ($expense, $currentBudget) {
                // 1. Refund the allowance back to the weekly budget balance
                $currentBudget->remaining_allowance += $expense->amount;
                $currentBudget->save();

                // 2. Rollback the Savings Goal progress if linked
                if ($expense->savings_goal_id) {
                    $goal = \App\Models\SavingsGoal::find($expense->savings_goal_id);
                    
                    if ($goal) {
                        // Deduct the money back out of the goal
                        $goal->current_saved -= $expense->amount;

                        // Safety threshold check
                        if ($goal->current_saved < 0) {
                            $goal->current_saved = 0.00;
                        }

                        // Revert status if it drops below target threshold
                        if ($goal->status === 'achieved' && $goal->current_saved < $goal->target_amount) {
                            $goal->status = 'active';
                        }

                        $goal->save();
                    }
                }

                // 3. Delete the transaction row safely
                $expense->delete();
            });

            $this->emit('refreshSavings');

            session()->flash('success', 'Transaction removed. Balance safely adjusted and savings progress updated!');
        } else {
            session()->flash('error', 'Unable to adjust framework. Active budget period not found.');
        }
    }

    public function render()
    {
        $allExpenses = Expense::where('user_id', auth()->id())
            ->with('category') 
            ->latest('id')
            ->paginate(10);

        return view('livewire.student.all-expenses', [
            'allExpenses' => $allExpenses
        ])->layout('layouts.student');
    }
}
