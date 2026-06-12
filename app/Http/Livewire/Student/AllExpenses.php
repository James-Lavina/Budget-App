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
                $currentBudget->remaining_allowance += $expense->amount;
                $currentBudget->save();

                $expense->delete();
            });

            session()->flash('success', 'Transaction removed. Balance safely adjusted!');
        } else {
            session()->flash('error', 'Unable to adjust framework. Active budget period not found.');
        }
    }

    public function render()
    {
        $allExpenses = Expense::where('user_id', auth()->id())
            ->with('category') 
            ->latest('transaction_date')
            ->paginate(10);

        return view('livewire.student.all-expenses', [
            'allExpenses' => $allExpenses
        ]);
    }
}
